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
 * Applies a postal address to a quote address, skipping blanks: an absent field in an update must not
 * erase the value an earlier request supplied.
 */
class AddressWriter
{
    /**
     * @param RegionResolver $regionResolver
     */
    public function __construct(
        private readonly RegionResolver $regionResolver
    ) {
    }

    /**
     * @param Address $address
     * @param PostalAddress $source
     * @return void
     */
    public function write(Address $address, PostalAddress $source): void
    {
        $street = [];

        foreach ([$source->streetLine, $source->extendedLine] as $line) {
            if ($this->present($line)) {
                $street[] = (string) $line;
            }
        }

        if ($street !== []) {
            $address->setStreet($street);
        }

        $this->set($source->locality, fn (string $value) => $address->setCity($value));
        $this->set($source->region, fn (string $value) => $address->setRegion($value));
        $this->set($source->country, fn (string $value) => $address->setCountryId($value));
        $this->set($source->postalCode, fn (string $value) => $address->setPostcode($value));
        $this->set($source->firstName, fn (string $value) => $address->setFirstname($value));
        $this->set($source->lastName, fn (string $value) => $address->setLastname($value));
        $this->set($source->phone, fn (string $value) => $address->setTelephone($value));
        $this->set($source->email, fn (string $value) => $address->setEmail($value));

        $this->writeRegionId($address);
    }

    /**
     * Placing an order needs the region's row id, which no wire address carries. Resolved from the
     * address as it now stands, because the country and the region can arrive on different requests.
     *
     * @param Address $address
     * @return void
     */
    private function writeRegionId(Address $address): void
    {
        $countryId = (string) $address->getCountryId();
        $region = $address->getData('region');

        if (!is_string($region)) {
            return;
        }

        $regionId = $this->regionResolver->resolve($countryId, $region);

        // Left unset when the country has no such region: an id guessed here would attach the address
        // to whichever region owns it, and Magento's own validation reports the miss.
        if ($regionId !== null) {
            $address->setRegionId($regionId);
        }
    }

    /**
     * @param string|null $value
     * @param callable(string): mixed $write
     * @return void
     */
    private function set(?string $value, callable $write): void
    {
        if ($this->present($value)) {
            $write((string) $value);
        }
    }

    /**
     * @param string|null $value
     * @return bool
     */
    private function present(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
