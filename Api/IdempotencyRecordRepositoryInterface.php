<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Api;

use Magebit\AgenticCore\Api\Data\IdempotencyRecordInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Storage for idempotency records, plus the atomic claim that arbitrates concurrent callers.
 */
interface IdempotencyRecordRepositoryInterface
{
    /**
     * @param IdempotencyRecordInterface $record
     * @return IdempotencyRecordInterface
     * @throws CouldNotSaveException
     */
    public function save(IdempotencyRecordInterface $record): IdempotencyRecordInterface;

    /**
     * @param string $scope
     * @param string $key
     * @return IdempotencyRecordInterface
     * @throws NoSuchEntityException
     */
    public function getByKey(string $scope, string $key): IdempotencyRecordInterface;

    /**
     * @param string $scope
     * @param string $key
     * @param string $requestHash
     * @return bool True when this caller won the claim.
     */
    public function claim(string $scope, string $key, string $requestHash): bool;

    /**
     * @param string $scope
     * @param string $key
     * @param string $requestHash
     * @param string $abandonedBefore UTC datetime; claims created before it are considered abandoned.
     * @return bool True when this caller took the claim over.
     */
    public function reclaimAbandoned(
        string $scope,
        string $key,
        string $requestHash,
        string $abandonedBefore
    ): bool;

    /**
     * @param string $expiredBefore UTC datetime; rows created before it are removed, across every scope.
     * @return int Number of deleted rows.
     */
    public function deleteExpired(string $expiredBefore): int;
}
