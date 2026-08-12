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
 * A postal address in no particular protocol's vocabulary. Each caller builds one from its own type.
 */
class PostalAddress
{
    /**
     * @param string|null $streetLine
     * @param string|null $extendedLine Second street line, when the caller supplies one
     * @param string|null $locality
     * @param string|null $region
     * @param string|null $country
     * @param string|null $postalCode
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $phone
     * @param string|null $email
     */
    public function __construct(
        public readonly ?string $streetLine = null,
        public readonly ?string $extendedLine = null,
        public readonly ?string $locality = null,
        public readonly ?string $region = null,
        public readonly ?string $country = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null
    ) {
    }
}
