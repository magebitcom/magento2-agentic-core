<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Checkout;

/**
 * The states a checkout can be in, independent of what any protocol calls them. Callers map each case
 * onto their own vocabulary, so adding a name upstream never reaches this enum.
 */
enum CheckoutState
{
    case Completed;
    case Canceled;
    case Incomplete;
    case RequiresEscalation;
    case Ready;
}
