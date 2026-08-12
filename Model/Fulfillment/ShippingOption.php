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

/**
 * One offerable shipping rate. All three amounts are integer minor units.
 */
class ShippingOption
{
    /**
     * @param string $id Carrier and method codes joined by an underscore
     * @param string $title
     * @param string|null $description Null when it would only repeat the title
     * @param string $carrier
     * @param int $amountExclTax
     * @param int $taxAmount
     * @param int $amountInclTax
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $carrier,
        public readonly int $amountExclTax,
        public readonly int $taxAmount,
        public readonly int $amountInclTax
    ) {
    }
}
