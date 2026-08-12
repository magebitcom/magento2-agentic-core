<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Quote;

use Magebit\AgenticCore\Model\Quote\AddressWriter;
use Magebit\AgenticCore\Model\Quote\PostalAddress;
use Magebit\AgenticCore\Model\Quote\RegionResolver;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;

class AddressWriterTest extends TestCase
{
    private AddressWriter $writer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $resolver = $this->createMock(RegionResolver::class);
        $resolver->method('resolve')->willReturnCallback(
            static fn (string $countryId, string $region): ?int => $region === 'Greater London' ? 271 : null
        );

        $this->writer = new AddressWriter($resolver);
    }

    /**
     * @return void
     */
    public function testEveryPresentFieldIsWritten(): void
    {
        $address = $this->address();

        $this->writer->write($address, new PostalAddress(
            streetLine: '1 Analytical Way',
            extendedLine: 'Suite 2',
            locality: 'London',
            region: 'Greater London',
            country: 'GB',
            postalCode: 'SW1A 1AA',
            firstName: 'Ada',
            lastName: 'Lovelace',
            phone: '+441234567890',
            email: 'ada@example.com'
        ));

        $this->assertSame(['1 Analytical Way', 'Suite 2'], $address->getStreet());
        $this->assertSame('London', $address->getCity());
        $this->assertSame('Greater London', $address->getData('region'));
        $this->assertSame('GB', $address->getCountryId());
        $this->assertSame('SW1A 1AA', $address->getPostcode());
        $this->assertSame('Ada', $address->getFirstname());
        $this->assertSame('Lovelace', $address->getLastname());
        $this->assertSame('+441234567890', $address->getTelephone());
        $this->assertSame('ada@example.com', $address->getEmail());
    }

    /**
     * Magento returns '' as readily as null for an unset address field, and a caller that writes the
     * blank through erases a value a previous request supplied.
     *
     * @return void
     */
    public function testBlankAndAbsentValuesLeaveTheAddressAlone(): void
    {
        $address = $this->address();
        $address->setCity('London');
        $address->setTelephone('+441234567890');

        $this->writer->write($address, new PostalAddress(locality: '', country: 'GB'));

        $this->assertSame('London', $address->getCity());
        $this->assertSame('+441234567890', $address->getTelephone());
        $this->assertSame('GB', $address->getCountryId());
    }

    /**
     * @return void
     */
    public function testTheExtendedLineIsDroppedWhenAbsent(): void
    {
        $address = $this->address();

        $this->writer->write($address, new PostalAddress(streetLine: '1 Analytical Way'));

        $this->assertSame(['1 Analytical Way'], $address->getStreet());
    }

    /**
     * Placing an order needs the region's row id, not its name: Magento rejects an address that carries
     * only the name with "regionId is required", so writing the name alone made completion unreachable.
     *
     * @return void
     */
    public function testTheRegionIdIsResolvedFromTheRegionName(): void
    {
        $address = $this->address();

        $this->writer->write($address, new PostalAddress(region: 'Greater London', country: 'GB'));

        $this->assertSame(271, $address->getData('region_id'));
    }

    /**
     * The country can have arrived on an earlier request, so the region has to be resolved against the
     * address as it now stands rather than against this payload alone.
     *
     * @return void
     */
    public function testTheRegionResolvesAgainstACountrySetEarlier(): void
    {
        $address = $this->address();
        $address->setCountryId('GB');

        $this->writer->write($address, new PostalAddress(region: 'Greater London'));

        $this->assertSame(271, $address->getData('region_id'));
    }

    /**
     * An unknown region must not be given an id, which would silently attach the address to whatever
     * region that id belongs to.
     *
     * @return void
     */
    public function testAnUnresolvableRegionLeavesTheIdUnset(): void
    {
        $address = $this->address();

        $this->writer->write($address, new PostalAddress(region: 'Atlantis', country: 'GB'));

        $this->assertNull($address->getData('region_id'));
    }

    /**
     * @return Address
     */
    private function address(): Address
    {
        return $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }
}
