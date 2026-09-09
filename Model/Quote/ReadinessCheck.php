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
 * What is still missing from an address. Split into who the person is and where they are, because
 * the two protocols keep those on different addresses and ask about them at different times.
 */
class ReadinessCheck
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
     * @return Requirement[]
     */
    public function contact(Address $address): array
    {
        $missing = [];

        if (!$address->getFirstname()) {
            $missing[] = Requirement::FirstName;
        }

        if (!$address->getLastname()) {
            $missing[] = Requirement::LastName;
        }

        if (!$address->getEmail()) {
            $missing[] = Requirement::Email;
        }

        if (!$address->getTelephone()) {
            $missing[] = Requirement::PhoneNumber;
        }

        return $missing;
    }

    /**
     * @param Address $address
     * @return Requirement[]
     */
    public function postal(Address $address): array
    {
        $missing = [];

        // The first line, not the array: an address with no street at all still reports one empty
        // line, so the array itself is never empty and asking whether it is says nothing.
        if (trim((string) $address->getStreetLine(1)) === '') {
            $missing[] = Requirement::Street;
        }

        if (!$address->getCity()) {
            $missing[] = Requirement::City;
        }

        if (!$address->getCountryId()) {
            $missing[] = Requirement::Country;
        }

        if (!$address->getPostcode()) {
            $missing[] = Requirement::Postcode;
        }

        return array_merge($missing, $this->region($address));
    }

    /**
     * Most countries have no regions, so one is asked for only where the store says it is needed.
     *
     * @param Address $address
     * @return Requirement[]
     */
    private function region(Address $address): array
    {
        $countryId = (string) $address->getCountryId();
        $region = $address->getData('region');
        $region = is_string($region) ? trim($region) : '';

        if ($countryId === '' || !$this->regionResolver->isRequiredFor($countryId)) {
            return [];
        }

        if ($region === '') {
            return [Requirement::Region];
        }

        return $this->regionResolver->resolve($countryId, $region) === null
            ? [Requirement::UnknownRegion]
            : [];
    }
}
