<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Quote;

use Magebit\AgenticCore\Model\Quote\LineItemOutcome;
use Magebit\AgenticCore\Model\Quote\LineItemWriter;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LineItemWriterTest extends TestCase
{
    private ProductRepositoryInterface&MockObject $productRepository;

    private LineItemWriter $writer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->writer = new LineItemWriter($this->productRepository);
    }

    /**
     * @return void
     */
    public function testTheQuoteIsClearedBeforeAnythingIsAdded(): void
    {
        $quote = $this->quote();
        $quote->expects($this->once())->method('removeAllItems');

        $this->productRepository->method('get')->willReturn($this->salableProduct());
        $quote->method('addProduct')->willReturn($this->createMock(Item::class));

        $this->writer->write($quote, [['sku' => 'sku-1', 'quantity' => 1]]);
    }

    /**
     * @return void
     */
    public function testASalableProductIsAdded(): void
    {
        $quote = $this->quote();
        $this->productRepository->method('get')->willReturn($this->salableProduct());
        $quote->expects($this->once())->method('addProduct')->willReturn($this->createMock(Item::class));

        $results = $this->writer->write($quote, [['sku' => 'sku-1', 'quantity' => 3]]);

        $this->assertCount(1, $results);
        $this->assertSame(LineItemOutcome::Added, $results[0]->outcome);
        $this->assertSame('sku-1', $results[0]->sku);
        $this->assertSame(0, $results[0]->index);
    }

    /**
     * @return void
     */
    public function testAnUnknownSkuIsReportedRatherThanThrown(): void
    {
        $quote = $this->quote();
        $this->productRepository->method('get')->willThrowException(new NoSuchEntityException(__('nope')));
        $quote->expects($this->never())->method('addProduct');

        $results = $this->writer->write($quote, [['sku' => 'ghost', 'quantity' => 1]]);

        $this->assertSame(LineItemOutcome::NotFound, $results[0]->outcome);
    }

    /**
     * @return void
     */
    public function testAnUnsalableProductIsReported(): void
    {
        $quote = $this->quote();
        $product = $this->createMock(Product::class);
        $product->method('isSalable')->willReturn(false);
        $this->productRepository->method('get')->willReturn($product);
        $quote->expects($this->never())->method('addProduct');

        $results = $this->writer->write($quote, [['sku' => 'sku-1', 'quantity' => 1]]);

        $this->assertSame(LineItemOutcome::NotSalable, $results[0]->outcome);
    }

    /**
     * @return void
     */
    public function testAMagentoRefusalCarriesItsMessage(): void
    {
        $quote = $this->quote();
        $this->productRepository->method('get')->willReturn($this->salableProduct());
        $quote->method('addProduct')
            ->willThrowException(new LocalizedException(__('Requested qty is not available')));

        $results = $this->writer->write($quote, [['sku' => 'sku-1', 'quantity' => 99]]);

        $this->assertSame(LineItemOutcome::Rejected, $results[0]->outcome);
        $this->assertSame('Requested qty is not available', $results[0]->reason);
    }

    /**
     * Quote::addProduct hands back a string instead of an item when the product cannot be configured.
     *
     * @return void
     */
    public function testAStringReturnFromAddProductIsARefusal(): void
    {
        $quote = $this->quote();
        $this->productRepository->method('get')->willReturn($this->salableProduct());
        $quote->method('addProduct')->willReturn('Please specify the product option');

        $results = $this->writer->write($quote, [['sku' => 'sku-1', 'quantity' => 1]]);

        $this->assertSame(LineItemOutcome::Rejected, $results[0]->outcome);
        $this->assertSame('Please specify the product option', $results[0]->reason);
    }

    /**
     * One bad SKU must not cost the agent the rest of its cart.
     *
     * @return void
     */
    public function testABadSkuDoesNotStopTheOnesAfterIt(): void
    {
        $quote = $this->quote();
        $this->productRepository->method('get')->willReturnCallback(
            function (string $sku): Product {
                if ($sku === 'ghost') {
                    throw new NoSuchEntityException(__('nope'));
                }

                return $this->salableProduct();
            }
        );
        $quote->method('addProduct')->willReturn($this->createMock(Item::class));

        $results = $this->writer->write($quote, [
            ['sku' => 'ghost', 'quantity' => 1],
            ['sku' => 'sku-2', 'quantity' => 2],
        ]);

        $this->assertSame(LineItemOutcome::NotFound, $results[0]->outcome);
        $this->assertSame(LineItemOutcome::Added, $results[1]->outcome);
        $this->assertSame(1, $results[1]->index);
    }

    /**
     * @return Product
     */
    private function salableProduct(): Product
    {
        $product = $this->createMock(Product::class);
        $product->method('isSalable')->willReturn(true);

        return $product;
    }

    /**
     * @return Quote&MockObject
     */
    private function quote(): Quote&MockObject
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['removeAllItems', 'addProduct', 'getStoreId'])
            ->getMock();
        $quote->method('getStoreId')->willReturn(1);

        return $quote;
    }
}
