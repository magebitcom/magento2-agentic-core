<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Buyer;

use Magento\Quote\Model\Quote;

/**
 * Resolves the buyer from a quote, preferring the customer record and falling back through the
 * billing then shipping address.
 */
class BuyerResolver
{
    /**
     * Typed against the concrete quote rather than CartInterface: the customer name fields and the
     * shipping address are not part of that contract.
     *
     * Billing precedes shipping because it identifies who is paying; shipping only names a
     * recipient, who may be someone else entirely.
     *
     * @param Quote $quote
     * @return BuyerIdentity
     */
    public function resolve(Quote $quote): BuyerIdentity
    {
        $billing = $quote->getBillingAddress();
        $shipping = $quote->getShippingAddress();

        return new BuyerIdentity(
            $this->firstNonEmpty(
                $quote->getCustomerFirstname(),
                $billing->getFirstname(),
                $shipping->getFirstname()
            ),
            $this->firstNonEmpty(
                $quote->getCustomerLastname(),
                $billing->getLastname(),
                $shipping->getLastname()
            ),
            $this->firstNonEmpty(
                $quote->getCustomerEmail(),
                $billing->getEmail(),
                $shipping->getEmail()
            ),
            $this->firstNonEmpty($billing->getTelephone(), $shipping->getTelephone())
        );
    }

    /**
     * Magento returns an empty string as readily as null for an unset address field, and an empty
     * string is a value every one of these payloads rejects.
     *
     * @param string|null ...$values
     * @return string|null
     */
    private function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
