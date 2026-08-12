<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Fulfillment;

use Magebit\AgenticCore\Model\Fulfillment\ShippingOptionResolver;
use Magebit\AgenticCore\Model\Money\MinorUnits;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use PHPUnit\Framework\TestCase;

/**
 * The amounts come from the framework's own rate-to-method conversion, which is where tax is applied;
 * this covers the resolver's own rules — what it offers and how it converts.
 */
class ShippingOptionResolverTest extends TestCase
{
    /**
     * @return void
     */
    public function testAnOfferableRateBecomesAnOptionInMinorUnits(): void
    {
        $options = $this->resolve([
            $this->rate('flatrate', 'flatrate', 'Flat Rate', 'Fixed', 5.00, 6.05),
        ]);

        $this->assertCount(1, $options);
        $this->assertSame('flatrate_flatrate', $options[0]->id);
        $this->assertSame('Fixed', $options[0]->title);
        $this->assertSame('Flat Rate', $options[0]->description);
        $this->assertSame('Flat Rate', $options[0]->carrier);
        $this->assertSame(500, $options[0]->amountExclTax);
        $this->assertSame(105, $options[0]->taxAmount);
        $this->assertSame(605, $options[0]->amountInclTax);
    }

    /**
     * A rate the carrier rejected is not an offer, and one caller was offering them.
     *
     * @return void
     */
    public function testARateCarryingAnErrorIsNotOffered(): void
    {
        $errored = $this->rate('ups', 'GND', 'UPS', 'Ground', 9.99, 9.99);
        $errored->setErrorMessage('This shipping method is not available.');

        $options = $this->resolve([
            $errored,
            $this->rate('flatrate', 'flatrate', 'Flat Rate', 'Fixed', 5.00, 5.00),
        ]);

        $this->assertCount(1, $options);
        $this->assertSame('flatrate_flatrate', $options[0]->id);
    }

    /**
     * The title falls back to the carrier when the method has no title of its own, and the description
     * is then dropped rather than repeating the same value twice.
     *
     * @return void
     */
    public function testAMethodWithNoTitleFallsBackToTheCarrier(): void
    {
        $options = $this->resolve([$this->rate('flatrate', 'flatrate', 'Flat Rate', '', 5.00, 5.00)]);

        $this->assertSame('Flat Rate', $options[0]->title);
        $this->assertNull($options[0]->description);
    }

    /**
     * Rates cannot be collected against an address with no country, and asking for them raises.
     *
     * @return void
     */
    public function testAnAddressWithNoCountryOffersNothing(): void
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCountryId', 'collectShippingRates', 'getAllShippingRates'])
            ->getMock();
        $address->method('getCountryId')->willReturn(null);
        $address->expects($this->never())->method('collectShippingRates');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getIsVirtual'])
            ->addMethods(['getQuoteCurrencyCode'])
            ->getMock();
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getIsVirtual')->willReturn(false);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');

        $this->assertSame([], $this->resolver()->resolve($quote));
    }

    /**
     * @return void
     */
    public function testAVirtualQuoteOffersNothing(): void
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIsVirtual', 'getShippingAddress'])
            ->getMock();
        $quote->method('getIsVirtual')->willReturn(true);
        $quote->expects($this->never())->method('getShippingAddress');

        $this->assertSame([], $this->resolver()->resolve($quote));
    }

    /**
     * A zero-decimal currency must not gain two digits.
     *
     * @return void
     */
    public function testAZeroDecimalCurrencyIsNotScaled(): void
    {
        $options = $this->resolve(
            [$this->rate('flatrate', 'flatrate', 'Flat Rate', 'Fixed', 500.0, 500.0)],
            'JPY'
        );

        $this->assertSame(500, $options[0]->amountInclTax);
        $this->assertSame(0, $options[0]->taxAmount);
    }

    /**
     * @param Rate[] $rates
     * @param string $currency
     * @return \Magebit\AgenticCore\Model\Fulfillment\ShippingOption[]
     */
    private function resolve(array $rates, string $currency = 'USD'): array
    {
        return $this->resolver()->resolve($this->quoteWith($rates, $currency));
    }

    /**
     * The converter is the framework's; it applies tax and currency conversion to a rate, and the
     * resolver reads its result rather than recomputing either.
     *
     * @return ShippingOptionResolver
     */
    private function resolver(): ShippingOptionResolver
    {
        $converter = $this->createMock(ShippingMethodConverter::class);
        $converter->method('modelToDataObject')->willReturnCallback(
            function (Rate $rate): ShippingMethodInterface {
                $method = $this->createMock(ShippingMethodInterface::class);
                $method->method('getPriceExclTax')->willReturn((float) $rate->getData('excl_tax'));
                $method->method('getPriceInclTax')->willReturn((float) $rate->getData('incl_tax'));

                return $method;
            }
        );

        return new ShippingOptionResolver($converter, new MinorUnits());
    }

    /**
     * @param string $carrier
     * @param string $method
     * @param string $carrierTitle
     * @param string $methodTitle
     * @param float $exclTax
     * @param float $inclTax
     * @return Rate
     */
    private function rate(
        string $carrier,
        string $method,
        string $carrierTitle,
        string $methodTitle,
        float $exclTax,
        float $inclTax
    ): Rate {
        $rate = $this->getMockBuilder(Rate::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $rate->setCarrier($carrier);
        $rate->setMethod($method);
        $rate->setCarrierTitle($carrierTitle);
        $rate->setMethodTitle($methodTitle);
        $rate->setPrice($exclTax);
        // Carried on the rate only so the converter stub above can hand them back.
        $rate->setData('excl_tax', $exclTax);
        $rate->setData('incl_tax', $inclTax);

        /** @var Rate $rate */
        return $rate;
    }

    /**
     * @param Rate[] $rates
     * @param string $currency
     * @return Quote
     */
    private function quoteWith(array $rates, string $currency = 'USD'): Quote
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCountryId', 'collectShippingRates', 'getAllShippingRates'])
            ->addMethods(['setCollectShippingRates'])
            ->getMock();
        $address->method('getCountryId')->willReturn('US');
        $address->method('getAllShippingRates')->willReturn($rates);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getIsVirtual'])
            ->addMethods(['getQuoteCurrencyCode'])
            ->getMock();
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getIsVirtual')->willReturn(false);
        $quote->method('getQuoteCurrencyCode')->willReturn($currency);

        return $quote;
    }
}
