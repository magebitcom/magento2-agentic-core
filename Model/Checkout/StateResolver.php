<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Checkout;

use Magento\Quote\Api\Data\CartInterface;

/**
 * Derives the checkout state from a quote, whether an order exists for it, and whether anything
 * still blocks completion.
 */
class StateResolver
{
    /**
     * @param CartInterface $quote
     * @param bool $hasOrder Whether an order has been placed against this quote
     * @param bool $isBlocked Whether readiness checks found anything preventing completion
     * @return CheckoutState
     */
    public function resolve(CartInterface $quote, bool $hasOrder, bool $isBlocked): CheckoutState
    {
        // Placing an order deactivates the quote, so an existing order has to outrank quote state —
        // otherwise every completed checkout reports itself canceled.
        if ($hasOrder) {
            return CheckoutState::Completed;
        }

        if (!$quote->getIsActive()) {
            return CheckoutState::Canceled;
        }

        return $isBlocked ? CheckoutState::Incomplete : CheckoutState::Ready;
    }
}
