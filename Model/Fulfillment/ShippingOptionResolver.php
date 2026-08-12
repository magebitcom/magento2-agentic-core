<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Fulfillment;

use Magebit\AgenticCore\Model\Money\MinorUnits;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate;

/**
 * Collects the quote's shipping rates and returns the offerable ones with amounts in minor units.
 */
class ShippingOptionResolver
{
    /**
     * @param ShippingMethodConverter $shippingMethodConverter Applies tax and currency conversion to a rate
     * @param MinorUnits $minorUnits
     */
    public function __construct(
        private readonly ShippingMethodConverter $shippingMethodConverter,
        private readonly MinorUnits $minorUnits
    ) {
    }

    /**
     * @param Quote $quote
     * @return ShippingOption[]
     */
    public function resolve(Quote $quote): array
    {
        if ($quote->getIsVirtual()) {
            return [];
        }

        $address = $quote->getShippingAddress();

        // Collecting rates against an address with no country raises rather than returning nothing.
        if (!$address->getCountryId()) {
            return [];
        }

        $address->setCollectShippingRates(true);
        $address->collectShippingRates();

        $currency = (string) ($quote->getQuoteCurrencyCode() ?: 'USD');
        $options = [];

        foreach ($address->getAllShippingRates() as $rate) {
            // A rate the carrier rejected is not an offer.
            if ($rate->getErrorMessage()) {
                continue;
            }

            $options[] = $this->toOption($rate, $currency);
        }

        return $options;
    }

    /**
     * A Rate carries neither tax nor the quote currency, so both come from the framework's converter
     * rather than being recomputed here.
     *
     * @param Rate $rate
     * @param string $currency
     * @return ShippingOption
     */
    private function toOption(Rate $rate, string $currency): ShippingOption
    {
        $method = $this->shippingMethodConverter->modelToDataObject($rate, $currency);

        $exclTax = (float) $method->getPriceExclTax();
        $inclTax = (float) $method->getPriceInclTax();

        $methodTitle = (string) $rate->getMethodTitle();
        $carrierTitle = (string) $rate->getCarrierTitle();

        return new ShippingOption(
            $rate->getCarrier() . '_' . $rate->getMethod(),
            $methodTitle !== '' ? $methodTitle : $carrierTitle,
            // Carried only when it would not repeat the title.
            $methodTitle !== '' ? $carrierTitle : null,
            $carrierTitle,
            $this->minorUnits->convert($exclTax, $currency),
            $this->minorUnits->convert($inclTax - $exclTax, $currency),
            $this->minorUnits->convert($inclTax, $currency)
        );
    }
}
