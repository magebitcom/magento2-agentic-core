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
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ResourceModel extends AbstractDb
{
    private const TABLE_NAME = 'agentic_idempotency';

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, IdempotencyRecordInterface::ENTITY_ID);
    }

    /**
     * Insert a claim row, relying on the unique key to arbitrate concurrent callers.
     *
     * @param string $scope
     * @param string $key
     * @param string $requestHash
     * @return bool True when this caller won the claim.
     */
    public function claim(string $scope, string $key, string $requestHash): bool
    {
        try {
            $this->connection()->insert($this->getMainTable(), [
                IdempotencyRecordInterface::SCOPE => $scope,
                IdempotencyRecordInterface::IDEMPOTENCY_KEY => $key,
                IdempotencyRecordInterface::REQUEST_HASH => $requestHash,
                IdempotencyRecordInterface::RESPONSE_STATUS => null,
                IdempotencyRecordInterface::RESPONSE_BODY => null,
            ]);
        } catch (DuplicateException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Take over a claim whose owner died before storing a response.
     *
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
    ): bool {
        $affected = $this->connection()->update(
            $this->getMainTable(),
            [
                IdempotencyRecordInterface::REQUEST_HASH => $requestHash,
                IdempotencyRecordInterface::CREATED_AT => new Expression('UTC_TIMESTAMP()'),
            ],
            [
                IdempotencyRecordInterface::SCOPE . ' = ?' => $scope,
                IdempotencyRecordInterface::IDEMPOTENCY_KEY . ' = ?' => $key,
                IdempotencyRecordInterface::RESPONSE_STATUS . ' IS NULL',
                IdempotencyRecordInterface::CREATED_AT . ' < ?' => $abandonedBefore,
            ]
        );

        return $affected > 0;
    }

    /**
     * A purge does not need partitioning: every scope's rows expire on the same rule.
     *
     * @param string $expiredBefore UTC datetime; rows created before it are removed.
     * @return int Number of deleted rows.
     */
    public function deleteExpired(string $expiredBefore): int
    {
        return $this->connection()->delete(
            $this->getMainTable(),
            [IdempotencyRecordInterface::CREATED_AT . ' < ?' => $expiredBefore]
        );
    }

    /**
     * getConnection() is typed AdapterInterface|false upstream, but a resource model
     * without a connection cannot work.
     *
     * @return AdapterInterface
     * @throws \RuntimeException
     */
    private function connection(): AdapterInterface
    {
        $connection = $this->getConnection();

        if ($connection === false) {
            throw new \RuntimeException('No database connection available.');
        }

        return $connection;
    }
}
