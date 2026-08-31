<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Quote;

/**
 * What happened to one submitted line item. Callers match over it to reach their own message codes.
 */
enum LineItemOutcome
{
    case Added;
    case NotFound;
    case NotSalable;
    case InsufficientStock;
    case InvalidQuantity;
    case Rejected;
}
