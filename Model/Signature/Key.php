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
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class Key extends AbstractModel implements SigningKeyInterface
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param EncryptorInterface $encryptor
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array<mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        private readonly EncryptorInterface $encryptor,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return string|null
     */
    public function getScope(): ?string
    {
        $value = $this->getData(self::SCOPE);

        return is_string($value) ? $value : null;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope): SigningKeyInterface
    {
        $this->setData(self::SCOPE, $scope);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        $value = $this->getData(self::STORE_ID);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): SigningKeyInterface
    {
        $this->setData(self::STORE_ID, $storeId);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKid(): ?string
    {
        $value = $this->getData(self::KID);

        return is_string($value) ? $value : null;
    }

    /**
     * @param string $kid
     * @return $this
     */
    public function setKid(string $kid): SigningKeyInterface
    {
        $this->setData(self::KID, $kid);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getPublicJwk(): array
    {
        $value = $this->getData(self::PUBLIC_JWK);

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_map(static fn (mixed $value): string => is_scalar($value) ? (string) $value : '', $decoded);
    }

    /**
     * @param array<string, string> $jwk
     * @return $this
     */
    public function setPublicJwk(array $jwk): SigningKeyInterface
    {
        $this->setData(self::PUBLIC_JWK, (string) json_encode($jwk));

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrivateKeyPem(): ?string
    {
        $value = $this->getData(self::PRIVATE_KEY);

        if (!is_string($value) || $value === '') {
            return null;
        }

        $pem = $this->encryptor->decrypt($value);

        return $pem === '' ? null : $pem;
    }

    /**
     * @param string $pem
     * @return $this
     */
    public function setPrivateKeyPem(string $pem): SigningKeyInterface
    {
        $this->setData(self::PRIVATE_KEY, $this->encryptor->encrypt($pem));

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): SigningKeyInterface
    {
        $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);

        return is_string($value) ? $value : null;
    }
}
