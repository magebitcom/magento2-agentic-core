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

/**
 * What the caller should do about this request's idempotency key. The wording of any refusal is the
 * caller's, because each protocol has its own error envelope.
 */
class Decision
{
    /**
     * @param DecisionOutcome $outcome
     * @param string|null $body The stored response, ready to send, when the outcome is a replay
     * @param int|null $status The status it was stored with
     */
    public function __construct(
        public readonly DecisionOutcome $outcome,
        public readonly ?string $body = null,
        public readonly ?int $status = null
    ) {
    }
}
