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

enum DecisionOutcome
{
    /**
     * Run the operation. Either the caller owns the key, or the request is not one keys apply to.
     */
    case Proceed;

    /**
     * Send the stored response back instead of running the operation again.
     */
    case Replay;

    /**
     * Another caller owns the key and has not finished. Ask for a retry.
     */
    case InFlight;

    /**
     * The key was already used for a different body.
     */
    case Conflict;

    /**
     * No key was sent on a request that must carry one.
     */
    case KeyMissing;
}
