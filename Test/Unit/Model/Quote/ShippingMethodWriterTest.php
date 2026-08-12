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

use Magebit\AgenticCore\Model\Quote\ShippingMethodWriter;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingMethodWriterTest extends TestCase
{
    private const METHOD = 'flatrate_flatrate';

    private ShippingMethodWriter $writer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->writer = new ShippingMethodWriter();
    }

    /**
     * @return void
     */
    public function testTheMethodIsSetOnTheAddressAndRatesAreFlaggedStale(): void
    {
        $address = $this->address();
        $address->expects($this->once())->method('setShippingMethod')->with(self::METHOD);
        $address->expects($this->once())->method('setCollectShippingRates')->with(true);

        $this->writer->write($this->quote($address, null), self::METHOD);
    }

    /**
     * Magento reads the method off the shipping assignment during total collection, so an address-only
     * write can be discarded.
     *
     * @return void
     */
    public function testTheMethodIsMirroredOntoTheShippingAssignment(): void
    {
        $shipping = $this->createMock(ShippingInterface::class);
        $shipping->expects($this->once())->method('setMethod')->with(self::METHOD);

        $assignment = $this->createMock(ShippingAssignmentInterface::class);
        $assignment->method('getShipping')->willReturn($shipping);

        $extension = $this->createMock(CartExtensionInterface::class);
        $extension->method('getShippingAssignments')->willReturn([$assignment]);

        $this->writer->write($this->quote($this->address(), $extension), self::METHOD);
    }

    /**
     * @return void
     */
    public function testAQuoteWithNoShippingAssignmentIsStillWritten(): void
    {
        $extension = $this->createMock(CartExtensionInterface::class);
        $extension->method('getShippingAssignments')->willReturn(null);

        $address = $this->address();
        $address->expects($this->once())->method('setShippingMethod')->with(self::METHOD);

        $this->writer->write($this->quote($address, $extension), self::METHOD);
    }

    /**
     * Both setters resolve through AbstractModel::__call, so they are added rather than overridden.
     *
     * @return Address&MockObject
     */
    private function address(): Address&MockObject
    {
        return $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->addMethods(['setShippingMethod', 'setCollectShippingRates'])
            ->getMock();
    }

    /**
     * @param Address $address
     * @param CartExtensionInterface|null $extension
     * @return Quote
     */
    private function quote(Address $address, ?CartExtensionInterface $extension): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getExtensionAttributes'])
            ->getMock();
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getExtensionAttributes')->willReturn($extension);

        return $quote;
    }
}
