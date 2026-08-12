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
use Magebit\AgenticCore\Api\Data\IdempotencyRecordInterfaceFactory;
use Magebit\AgenticCore\Api\IdempotencyRecordRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Decides whether a caller owns a key, must wait, must be refused, or has a response to replay.
 */
class Coordinator
{
    /**
     * A claim older than this with no stored response is treated as abandoned by a crashed request.
     */
    public const ABANDONED_CLAIM_SECONDS = 60;

    /**
     * @param IdempotencyRecordRepositoryInterface $repository
     * @param IdempotencyRecordInterfaceFactory $recordFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly IdempotencyRecordRepositoryInterface $repository,
        private readonly IdempotencyRecordInterfaceFactory $recordFactory,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @param string $scope
     * @param string $key
     * @param string $requestHash
     * @return ClaimResult
     */
    public function claim(string $scope, string $key, string $requestHash): ClaimResult
    {
        if ($this->repository->claim($scope, $key, $requestHash)) {
            return new ClaimResult(ClaimOutcome::Claimed);
        }

        try {
            $record = $this->repository->getByKey($scope, $key);
        } catch (NoSuchEntityException $exception) {
            // Purged between the failed claim and this read; nothing to replay and nothing to block.
            return new ClaimResult(ClaimOutcome::Claimed);
        }

        if ($record->getRequestHash() !== $requestHash) {
            return new ClaimResult(ClaimOutcome::Conflict, $record);
        }

        if ($record->getResponseStatus() !== null) {
            return new ClaimResult(ClaimOutcome::Replay, $record);
        }

        $abandonedBefore = $this->dateTime->gmtDate(
            'Y-m-d H:i:s',
            $this->dateTime->gmtTimestamp() - self::ABANDONED_CLAIM_SECONDS
        );

        if ($this->repository->reclaimAbandoned($scope, $key, $requestHash, $abandonedBefore)) {
            return new ClaimResult(ClaimOutcome::Claimed);
        }

        return new ClaimResult(ClaimOutcome::InFlight, $record);
    }

    /**
     * Completes the row claim() reserved, rather than inserting a second one.
     *
     * @param string $scope
     * @param string $key
     * @param string $requestHash
     * @param int $status
     * @param string $body Stored and replayed byte for byte; encrypt at the call site if needed.
     * @return IdempotencyRecordInterface
     * @throws CouldNotSaveException
     */
    public function storeResponse(
        string $scope,
        string $key,
        string $requestHash,
        int $status,
        string $body
    ): IdempotencyRecordInterface {
        try {
            $record = $this->repository->getByKey($scope, $key);
        } catch (NoSuchEntityException $exception) {
            /** @var IdempotencyRecordInterface $record */
            $record = $this->recordFactory->create();
            $record->setScope($scope);
            $record->setIdempotencyKey($key);
        }

        $record->setRequestHash($requestHash);
        $record->setResponseStatus($status);
        $record->setResponseBody($body);

        return $this->repository->save($record);
    }
}
