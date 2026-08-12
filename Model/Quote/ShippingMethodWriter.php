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

use Magento\Quote\Model\Quote;

class ShippingMethodWriter
{
    /**
     * Magento reads the method off the shipping assignment during total collection, so writing only
     * the address field can be discarded.
     *
     * @param Quote $quote
     * @param string $method Carrier and method codes joined by an underscore
     * @return void
     */
    public function write(Quote $quote, string $method): void
    {
        $address = $quote->getShippingAddress();
        $address->setShippingMethod($method);

        $extension = $quote->getExtensionAttributes();
        $assignments = $extension?->getShippingAssignments() ?? [];

        if ($assignments !== []) {
            $assignments[0]->getShipping()->setMethod($method);
        }

        // Rates cached against the previous selection are stale once the method moves.
        $address->setCollectShippingRates(true);
    }
}
