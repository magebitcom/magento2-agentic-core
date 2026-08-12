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

/**
 * Whether a delivery arrived, is worth another attempt, or was refused in a way retrying cannot fix.
 */
enum SendOutcome
{
    case Delivered;
    case Retryable;
    case Permanent;
}
