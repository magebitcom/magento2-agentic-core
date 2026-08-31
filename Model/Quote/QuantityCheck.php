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

use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\DataObject;

/**
 * Asks Magento whether it will sell a given number of units, so the answer comes from the store's own
 * rules rather than from reading its error text.
 */
class QuantityCheck
{
    /**
     * Magento's reasons that are about the number asked for rather than the units being there: below a
     * minimum, above a per-cart cap, off the selling increment, or not a whole number. Both namings are
     * listed because single-source stores report the first set and multi-source stores the second.
     * Every other reason it gives means the store cannot supply that many.
     */
    private const QUANTITY_ERROR_CODES = [
        'qty_min',
        'qty_max',
        'qty_increments',
        'qty_decimal',
        'is_correct_qty-min_sale_qty',
        'is_correct_qty-max_sale_qty',
        'is_correct_qty-qty_increment',
        'is_correct_qty-is_qty_decimal',
    ];

    /**
     * @param StockStateInterface $stockState
     */
    public function __construct(
        private readonly StockStateInterface $stockState
    ) {
    }

    /**
     * @param int $productId
     * @param int $quantity
     * @return QuantityRefusal|null Null when the store will sell that many
     */
    public function refuse(int $productId, int $quantity): ?QuantityRefusal
    {
        // The interface says int, but every implementation hands back the check's result object.
        /** @var mixed $result */
        $result = $this->stockState->checkQuoteItemQty($productId, $quantity, $quantity, $quantity);

        if (!$result instanceof DataObject || !$result->getData('has_error')) {
            return null;
        }

        $errorCode = $result->getData('error_code');
        $outcome = is_string($errorCode) && in_array($errorCode, self::QUANTITY_ERROR_CODES, true)
            ? LineItemOutcome::InvalidQuantity
            : LineItemOutcome::InsufficientStock;

        return new QuantityRefusal($outcome, $this->text($result->getData('message')));
    }

    /**
     * The message arrives as a translated phrase on some paths and a plain string on others.
     *
     * @param mixed $message
     * @return string
     */
    private function text(mixed $message): string
    {
        if (is_string($message)) {
            return $message;
        }

        return $message instanceof \Stringable ? (string) $message : '';
    }
}
