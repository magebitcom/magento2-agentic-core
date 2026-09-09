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
use Magento\Quote\Model\Quote\Address;

/**
 * Writes a buyer onto a quote. The mirror of BuyerResolver, which reads one back off it.
 *
 * An absent field leaves what is already there alone, so a later request cannot blank a value by
 * omitting it. Which address the contact details go on is the caller's decision: the two protocols
 * carry the delivery destination in different places.
 */
class BuyerWriter
{
    /**
     * The fields Magento keeps on the quote itself rather than on either address.
     *
     * @param Quote $quote
     * @param BuyerIdentity $identity
     * @return void
     */
    public function writeCustomer(Quote $quote, BuyerIdentity $identity): void
    {
        if ($identity->firstName !== null) {
            $quote->setCustomerFirstname($identity->firstName);
        }

        if ($identity->lastName !== null) {
            $quote->setCustomerLastname($identity->lastName);
        }

        if ($identity->email !== null) {
            $quote->setCustomerEmail($identity->email);
        }
    }

    /**
     * @param Address $address
     * @param BuyerIdentity $identity
     * @return void
     */
    public function writeContact(Address $address, BuyerIdentity $identity): void
    {
        if ($identity->firstName !== null) {
            $address->setFirstname($identity->firstName);
        }

        if ($identity->lastName !== null) {
            $address->setLastname($identity->lastName);
        }

        if ($identity->email !== null) {
            $address->setEmail($identity->email);
        }

        // Quote addresses store the phone under `telephone`; setPhoneNumber() writes an unread key.
        if ($identity->phoneNumber !== null) {
            $address->setTelephone($identity->phoneNumber);
        }
    }
}
