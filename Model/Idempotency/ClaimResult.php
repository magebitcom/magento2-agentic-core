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

class ClaimResult
{
    /**
     * @param ClaimOutcome $outcome
     * @param IdempotencyRecordInterface|null $record Present for Replay, Conflict and InFlight.
     */
    public function __construct(
        public readonly ClaimOutcome $outcome,
        public readonly ?IdempotencyRecordInterface $record = null
    ) {
    }
}
