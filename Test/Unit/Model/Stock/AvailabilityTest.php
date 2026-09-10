<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Stock;

use ArrayIterator;
use Magebit\AgenticCore\Model\Stock\Availability;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class AvailabilityTest extends TestCase
{
    /**
     * Every SKU the registry was asked about one at a time.
     *
     * @var string[]
     */
    private array $asked = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->asked = [];
    }

    /**
     * @return void
     */
    public function testAProductWithStockCanBeBought(): void
    {
        $this->assertTrue($this->availability()->isSalable('sku-one'));
    }

    /**
     * @return void
     */
    public function testAProductWithNoStockRecordCannotBeBought(): void
    {
        $this->assertFalse($this->availability()->isSalable('sku-missing'));
    }

    /**
     * A page of products used to cost two queries each. Once the page has been read, the answers are
     * already there.
     *
     * @return void
     */
    public function testAPrefetchedProductIsNotAskedAboutAgain(): void
    {
        $availability = $this->availability();
        $availability->prefetch($this->collection(['sku-one' => true, 'sku-two' => false]));

        $this->assertTrue($availability->isSalable('sku-one'));
        $this->assertFalse($availability->isSalable('sku-two'));
        $this->assertSame([], $this->asked);
    }

    /**
     * @return void
     */
    public function testAProductThePrefetchMissedIsStillAskedAbout(): void
    {
        $availability = $this->availability();
        $availability->prefetch($this->collection(['sku-one' => true]));

        $this->assertTrue($availability->isSalable('sku-other'));
        $this->assertSame(['sku-other'], $this->asked);
    }

    /**
     * @return void
     */
    public function testTheSameProductIsOnlyAskedAboutOnce(): void
    {
        $availability = $this->availability();

        $availability->isSalable('sku-one');
        $availability->isSalable('sku-one');

        $this->assertSame(['sku-one'], $this->asked);
    }

    /**
     * A long-running process reuses the object, so what it remembers must not outlive the request.
     *
     * @return void
     */
    public function testWhatIsRememberedIsDroppedBetweenRequests(): void
    {
        $availability = $this->availability();
        $availability->prefetch($this->collection(['sku-one' => false]));

        $availability->_resetState();

        $this->assertTrue($availability->isSalable('sku-one'));
        $this->assertSame(['sku-one'], $this->asked);
    }

    /**
     * @return Availability
     */
    private function availability(): Availability
    {
        $registry = $this->createMock(StockRegistryInterface::class);
        $registry->method('getProductStockStatusBySku')->willReturnCallback(
            function (string $sku): int {
                $this->asked[] = $sku;

                if ($sku === 'sku-missing') {
                    throw new NoSuchEntityException(__('No such product.'));
                }

                return 1;
            }
        );

        return new Availability($registry, $this->createMock(StockHelper::class));
    }

    /**
     * A stand-in for a loaded page of products, each already carrying the stock the batch call read.
     *
     * @param array<string, bool> $salable
     * @return ProductCollection
     */
    private function collection(array $salable): ProductCollection
    {
        $items = [];

        foreach ($salable as $sku => $isSalable) {
            $product = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
            $product->method('getData')->willReturnCallback(
                static fn (string $key): mixed => $key === 'sku' ? $sku : $isSalable
            );
            $items[] = $product;
        }

        $collection = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems', 'getIterator'])
            ->getMock();
        $collection->method('getItems')->willReturn($items);
        $collection->method('getIterator')->willReturn(new ArrayIterator($items));

        return $collection;
    }
}
