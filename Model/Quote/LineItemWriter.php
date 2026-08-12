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

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;

class LineItemWriter
{
    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Replaces the quote's items, reporting each input rather than failing the whole write.
     *
     * @param Quote $quote
     * @param array<array{sku: string, quantity: int}> $items
     * @return LineItemResult[] One per input, in input order
     */
    public function write(Quote $quote, array $items): array
    {
        $quote->removeAllItems();

        $results = [];

        foreach (array_values($items) as $index => $item) {
            $results[] = $this->add($quote, $index, $item['sku'], $item['quantity']);
        }

        return $results;
    }

    /**
     * @param Quote $quote
     * @param int $index
     * @param string $sku
     * @param int $quantity
     * @return LineItemResult
     */
    private function add(Quote $quote, int $index, string $sku, int $quantity): LineItemResult
    {
        try {
            // Store-scoped: a product loaded in the wrong scope prices against the wrong website.
            /** @var Product $product */
            $product = $this->productRepository->get($sku, false, (int) $quote->getStoreId());
        } catch (NoSuchEntityException $exception) {
            return new LineItemResult($index, $sku, LineItemOutcome::NotFound);
        }

        if (!$product->isSalable()) {
            return new LineItemResult($index, $sku, LineItemOutcome::NotSalable);
        }

        try {
            $result = $quote->addProduct($product, $quantity);
        } catch (LocalizedException $exception) {
            return new LineItemResult($index, $sku, LineItemOutcome::Rejected, $exception->getMessage());
        }

        // Quote::addProduct hands back a string instead of an item when the product cannot be configured.
        if (is_string($result)) {
            return new LineItemResult($index, $sku, LineItemOutcome::Rejected, $result);
        }

        return new LineItemResult($index, $sku, LineItemOutcome::Added);
    }
}
