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

use Magento\Quote\Model\Quote\Address;

/**
 * Copies fields between two quote addresses. Stateless — both callers pass the pair they already hold.
 */
class PersonalInformationCopier
{
    /**
     * Copies who the person is, not where they are.
     *
     * @param Address $from
     * @param Address $to
     * @return void
     */
    public function copyIdentity(Address $from, Address $to): void
    {
        $this->copy($from->getFirstname(), fn (string $value) => $to->setFirstname($value));
        $this->copy($from->getLastname(), fn (string $value) => $to->setLastname($value));
        $this->copy($from->getEmail(), fn (string $value) => $to->setEmail($value));
        // Quote addresses store the phone under `telephone`; getPhoneNumber() resolves to an unset key.
        $this->copy($from->getTelephone(), fn (string $value) => $to->setTelephone($value));
    }

    /**
     * @param Address $from
     * @param Address $to
     * @return void
     */
    public function copyPostalFields(Address $from, Address $to): void
    {
        $street = $from->getStreet();

        if ($street !== []) {
            $to->setStreet($street);
        }

        $this->copy($from->getCity(), fn (string $value) => $to->setCity($value));
        $this->copy($from->getCountryId(), fn (string $value) => $to->setCountryId($value));
        $this->copy($from->getPostcode(), fn (string $value) => $to->setPostcode($value));

        if ($from->getRegionId()) {
            $to->setRegionId($from->getRegionId());
        }
    }

    /**
     * @param string|null $value
     * @param callable(string): mixed $write
     * @return void
     */
    private function copy(?string $value, callable $write): void
    {
        if ($value !== null && trim($value) !== '') {
            $write($value);
        }
    }
}
