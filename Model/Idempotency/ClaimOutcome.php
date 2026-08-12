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
 * The four ways a claim can resolve. Carries no strings: callers match over it to reach their own
 * status codes and error vocabulary, which is the only reason this behaviour can be shared.
 */
enum ClaimOutcome
{
    case Claimed;
    case InFlight;
    case Conflict;
    case Replay;
}
