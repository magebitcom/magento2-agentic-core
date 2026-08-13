<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Signature;

use Magebit\AgenticCore\Api\Data\SigningKeyInterface;
use Magebit\AgenticCore\Api\SigningKeyRepositoryInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Repository implements SigningKeyRepositoryInterface
{
    /**
     * Enough entropy that two stores rotating in the same second cannot collide.
     */
    private const KID_BYTES = 8;

    /**
     * @param KeyFactory $keyFactory
     * @param CollectionFactory $collectionFactory
     * @param ResourceModel $resource
     * @param KeyPairGenerator $generator
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly KeyFactory $keyFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceModel $resource,
        private readonly KeyPairGenerator $generator,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface
     * @throws \RuntimeException
     */
    public function getSigningKey(string $scope, int $storeId): SigningKeyInterface
    {
        $existing = $this->newestActive($scope, $storeId);

        if ($existing !== null) {
            return $existing;
        }

        try {
            return $this->create($scope, $storeId);
        } catch (AlreadyExistsException $exception) {
            // Two requests generated at once. The other one won, and its key is as good as ours.
            $winner = $this->newestActive($scope, $storeId);

            if ($winner === null) {
                throw new \RuntimeException('A signing key could not be created.', 0, $exception);
            }

            return $winner;
        }
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface[]
     */
    public function getPublishableKeys(string $scope, int $storeId): array
    {
        $collection = $this->scoped($scope, $storeId);
        $collection->setOrder(SigningKeyInterface::ENTITY_ID, 'DESC');

        $keys = [];

        foreach ($collection as $key) {
            if ($key instanceof SigningKeyInterface) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface
     * @throws \RuntimeException
     */
    public function rotate(string $scope, int $storeId): SigningKeyInterface
    {
        // Retired before the new one is created: if creation fails, nothing is left signing with a key
        // the merchant asked to stop using.
        foreach ($this->getPublishableKeys($scope, $storeId) as $key) {
            if ($key->isActive()) {
                $this->save($key->setIsActive(false));
            }
        }

        try {
            return $this->create($scope, $storeId);
        } catch (AlreadyExistsException $exception) {
            throw new \RuntimeException('A replacement signing key could not be created.', 0, $exception);
        }
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @param int $graceDays
     * @return int
     */
    public function purgeRetired(string $scope, int $storeId, int $graceDays): int
    {
        $cutoff = $this->dateTime->gmtDate('Y-m-d H:i:s', $this->dateTime->gmtTimestamp() - $graceDays * 86400);
        $removed = 0;

        foreach ($this->getPublishableKeys($scope, $storeId) as $key) {
            if (!$key instanceof Key || $key->isActive() || (string) $key->getCreatedAt() >= $cutoff) {
                continue;
            }

            $this->resource->delete($key);
            $removed++;
        }

        return $removed;
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface
     * @throws AlreadyExistsException
     */
    private function create(string $scope, int $storeId): SigningKeyInterface
    {
        $pair = $this->generator->generate(bin2hex(random_bytes(self::KID_BYTES)));
        $key = $this->keyFactory->create();

        $key->setScope($scope)
            ->setStoreId($storeId)
            ->setKid($pair->getKeyId())
            ->setPublicJwk($pair->getPublicJwk())
            ->setPrivateKeyPem($pair->getPrivateKeyPem())
            ->setIsActive(true);

        $this->save($key);

        return $key;
    }

    /**
     * @param SigningKeyInterface $key
     * @return void
     * @throws AlreadyExistsException
     */
    private function save(SigningKeyInterface $key): void
    {
        if (!$key instanceof Key) {
            throw new \InvalidArgumentException('Only keys from this repository can be saved.');
        }

        $this->resource->save($key);
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface|null
     */
    private function newestActive(string $scope, int $storeId): ?SigningKeyInterface
    {
        foreach ($this->getPublishableKeys($scope, $storeId) as $key) {
            if ($key->isActive() && $key->getPrivateKeyPem() !== null) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param string $scope
     * @param int $storeId
     * @return Collection
     */
    private function scoped(string $scope, int $storeId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(SigningKeyInterface::SCOPE, $scope);
        $collection->addFieldToFilter(SigningKeyInterface::STORE_ID, (string) $storeId);

        return $collection;
    }
}
