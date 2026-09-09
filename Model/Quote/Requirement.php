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
 * One thing a quote still needs before an order can be placed from it. Each caller turns these into
 * whatever its protocol reports, because the field names and paths on the wire are not shared.
 */
enum Requirement
{
    case FirstName;
    case LastName;
    case Email;
    case PhoneNumber;
    case Street;
    case City;
    case Country;
    case Postcode;

    /**
     * The country has regions and none was given.
     */
    case Region;

    /**
     * A region was given, but it is not one the country has. Reported apart from a missing one,
     * because a name nobody can resolve would otherwise only surface as a failure at completion.
     */
    case UnknownRegion;
}
