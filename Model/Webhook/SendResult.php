<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Webhook;

class SendResult
{
    /**
     * @param SendOutcome $outcome
     * @param int $status Zero when the attempt never reached the receiver
     * @param string|null $error Response body, or the transport failure's message
     */
    public function __construct(
        public readonly SendOutcome $outcome,
        public readonly int $status,
        public readonly ?string $error = null
    ) {
    }
}
