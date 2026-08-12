<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Idempotency;

use Magebit\AgenticCore\Api\Data\IdempotencyRecordInterface;
use Magebit\AgenticCore\Api\IdempotencyRecordRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Repository implements IdempotencyRecordRepositoryInterface
{
    /**
     * @param ResourceModel $resourceModel
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceModel $resourceModel,
        private readonly CollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(IdempotencyRecordInterface $record): IdempotencyRecordInterface
    {
        try {
            /** @var Record $record */
            $this->resourceModel->save($record);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new CouldNotSaveException(
                __('Could not save the idempotency record: %1', $exception->getMessage()),
                $exception
            );
        }

        return $record;
    }

    /**
     * The identity is two columns, so this reads through the collection rather than load().
     *
     * @inheritDoc
     */
    public function getByKey(string $scope, string $key): IdempotencyRecordInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(IdempotencyRecordInterface::SCOPE, $scope);
        $collection->addFieldToFilter(IdempotencyRecordInterface::IDEMPOTENCY_KEY, $key);
        $collection->setPageSize(1);

        /** @var IdempotencyRecordInterface|null $record */
        $record = $collection->getFirstItem();

        if (!$record || !$record->getEntityId()) {
            throw new NoSuchEntityException(
                __('No idempotency record exists for key "%1" in scope "%2".', $key, $scope)
            );
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function claim(string $scope, string $key, string $requestHash): bool
    {
        return $this->resourceModel->claim($scope, $key, $requestHash);
    }

    /**
     * @inheritDoc
     */
    public function reclaimAbandoned(
        string $scope,
        string $key,
        string $requestHash,
        string $abandonedBefore
    ): bool {
        return $this->resourceModel->reclaimAbandoned($scope, $key, $requestHash, $abandonedBefore);
    }

    /**
     * @inheritDoc
     */
    public function deleteExpired(string $expiredBefore): int
    {
        return $this->resourceModel->deleteExpired($expiredBefore);
    }
}
