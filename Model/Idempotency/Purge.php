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

use Magebit\AgenticCore\Api\IdempotencyRecordRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Purge
{
    private const SECONDS_PER_HOUR = 3600;

    /**
     * @param IdempotencyRecordRepositoryInterface $repository
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly IdempotencyRecordRepositoryInterface $repository,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * The lifetime is a parameter rather than a config read: each caller owns its own setting.
     *
     * @param int $ttlHours A value below one disables the purge.
     * @return int Number of deleted rows.
     */
    public function execute(int $ttlHours): int
    {
        if ($ttlHours < 1) {
            return 0;
        }

        $expiredBefore = $this->dateTime->gmtDate(
            'Y-m-d H:i:s',
            $this->dateTime->gmtTimestamp() - $ttlHours * self::SECONDS_PER_HOUR
        );

        return $this->repository->deleteExpired($expiredBefore);
    }
}
