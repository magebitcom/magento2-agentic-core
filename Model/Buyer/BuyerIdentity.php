<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Buyer;

/**
 * The buyer fields every protocol asks for, resolved from a quote. Each field is independently
 * optional, so callers must check before mapping onto a payload that forbids empty strings.
 */
class BuyerIdentity
{
    /**
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $email
     * @param string|null $phoneNumber
     */
    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $phoneNumber
    ) {
    }

    /**
     * @return bool Whether every field is absent, in which case there is no buyer to report
     */
    public function isEmpty(): bool
    {
        return $this->firstName === null
            && $this->lastName === null
            && $this->email === null
            && $this->phoneNumber === null;
    }
}
