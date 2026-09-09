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

use Magebit\AgenticCore\Model\Quote\ReadinessCheck;
use Magebit\AgenticCore\Model\Quote\RegionResolver;
use Magebit\AgenticCore\Model\Quote\Requirement;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadinessCheckTest extends TestCase
{
    /**
     * @var RegionResolver&MockObject
     */
    private RegionResolver $regionResolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->regionResolver = $this->createMock(RegionResolver::class);
    }

    /**
     * @return void
     */
    public function testAnEmptyAddressNeedsEveryContactField(): void
    {
        $this->assertSame(
            [
                Requirement::FirstName,
                Requirement::LastName,
                Requirement::Email,
                Requirement::PhoneNumber,
            ],
            $this->check()->contact($this->address())
        );
    }

    /**
     * @return void
     */
    public function testACompleteContactNeedsNothing(): void
    {
        $address = $this->address();
        $address->setFirstname('Ada')
            ->setLastname('Lovelace')
            ->setEmail('ada@example.com')
            ->setTelephone('+15125550100');

        $this->assertSame([], $this->check()->contact($address));
    }

    /**
     * @return void
     */
    public function testAnEmptyAddressNeedsEveryPostalField(): void
    {
        $this->assertSame(
            [
                Requirement::Street,
                Requirement::City,
                Requirement::Country,
                Requirement::Postcode,
            ],
            $this->check()->postal($this->address())
        );
    }

    /**
     * Most countries have no regions, so one must not be demanded where the store says none is
     * needed. A wrongly demanded region blocks an order that would otherwise go through.
     *
     * @return void
     */
    public function testNoRegionIsDemandedWhereTheCountryHasNone(): void
    {
        $this->regionResolver->method('isRequiredFor')->willReturn(false);

        $this->assertSame([], $this->check()->postal($this->postalAddress()));
    }

    /**
     * @return void
     */
    public function testAMissingRegionIsReportedWhereOneIsNeeded(): void
    {
        $this->regionResolver->method('isRequiredFor')->willReturn(true);

        $this->assertSame([Requirement::Region], $this->check()->postal($this->postalAddress()));
    }

    /**
     * A name nobody can resolve would otherwise only surface as a failure at completion, with
     * nothing to say which field was wrong.
     *
     * @return void
     */
    public function testARegionTheCountryDoesNotHaveIsReportedApart(): void
    {
        $this->regionResolver->method('isRequiredFor')->willReturn(true);
        $this->regionResolver->method('resolve')->willReturn(null);

        $address = $this->postalAddress();
        $address->setData('region', 'Atlantis');

        $this->assertSame([Requirement::UnknownRegion], $this->check()->postal($address));
    }

    /**
     * @return void
     */
    public function testARegionTheCountryHasIsAccepted(): void
    {
        $this->regionResolver->method('isRequiredFor')->willReturn(true);
        $this->regionResolver->method('resolve')->willReturn(57);

        $address = $this->postalAddress();
        $address->setData('region', 'Texas');

        $this->assertSame([], $this->check()->postal($address));
    }

    /**
     * @return ReadinessCheck
     */
    private function check(): ReadinessCheck
    {
        return new ReadinessCheck($this->regionResolver);
    }

    /**
     * @return Address An address with everything but the region filled in
     */
    private function postalAddress(): Address
    {
        $address = $this->address();
        $address->setStreet(['1 Analytical Way'])
            ->setCity('Austin')
            ->setCountryId('US')
            ->setPostcode('78701');

        return $address;
    }

    /**
     * @return Address
     */
    private function address(): Address
    {
        /** @var Address $address */
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        return $address;
    }
}
