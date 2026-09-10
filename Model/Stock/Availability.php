<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Stock;

use Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection;
use Magento\Framework\DataObject;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Whether a SKU can be bought. Asked of the stock registry rather than the product, because a product
 * loaded through a collection carries no stock data and reports itself salable regardless.
 */
class Availability implements ResetAfterRequestInterface
{
    /**
     * Answers already given during this request, keyed by SKU.
     *
     * @var array<string, bool>
     */
    private array $known = [];

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param StockHelper $stockHelper
     */
    public function __construct(
        private readonly StockRegistryInterface $stockRegistry,
        private readonly StockHelper $stockHelper
    ) {
    }

    /**
     * @param string $sku
     * @return bool
     */
    public function isSalable(string $sku): bool
    {
        return $this->known[$sku] ??= $this->ask($sku);
    }

    /**
     * Answers for a whole collection at once. Asking one product at a time cost two queries each, so a
     * page of products with variants ran into the hundreds.
     *
     * @param AbstractCollection $products
     * @return void
     */
    public function prefetch(AbstractCollection $products): void
    {
        if ($products->getItems() === []) {
            return;
        }

        // Marked deprecated in favour of Multi Source Inventory, but it is the only batch call the
        // stock API this module already uses has, and it gives the same answer as asking one at a time.
        $this->stockHelper->addStockStatusToProducts($products);

        foreach ($products as $product) {
            if (!$product instanceof DataObject) {
                continue;
            }

            $sku = $product->getData('sku');

            if (is_string($sku)) {
                $this->known[$sku] = (bool) $product->getData('is_salable');
            }
        }
    }

    /**
     * @return void
     */
    public function _resetState(): void
    {
        $this->known = [];
    }

    /**
     * @param string $sku
     * @return bool
     */
    private function ask(string $sku): bool
    {
        try {
            return (bool) $this->stockRegistry->getProductStockStatusBySku($sku);
        } catch (NoSuchEntityException $exception) {
            // No stock record at all: nothing says it can be bought.
            return false;
        }
    }
}
