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
use Magebit\AgenticCore\Model\Quote\QuantityCheck;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuantityCheckTest extends TestCase
{
    private StockStateInterface&MockObject $stockState;

    private QuantityCheck $check;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->stockState = $this->createMock(StockStateInterface::class);
        $this->check = new QuantityCheck($this->stockState);
    }

    /**
     * @return void
     */
    public function testAQuantityTheStoreAcceptsIsNotRefused(): void
    {
        $this->stockStateReturns(new DataObject(['has_error' => false]));

        $this->assertNull($this->check->refuse(1, 3));
    }

    /**
     * The interface's return type says int, so anything unexpected must not be read as a refusal.
     *
     * @return void
     */
    public function testANonObjectAnswerIsNotRefused(): void
    {
        $this->stockState->method('checkQuoteItemQty')->willReturn(0);

        $this->assertNull($this->check->refuse(1, 3));
    }

    /**
     * @param string $errorCode
     * @param LineItemOutcome $expected
     * @return void
     * @dataProvider errorCodeProvider
     */
    public function testTheStoresOwnErrorCodeDecidesTheOutcome(
        string $errorCode,
        LineItemOutcome $expected
    ): void {
        $this->stockStateReturns(new DataObject([
            'has_error' => true,
            'error_code' => $errorCode,
            'message' => 'Nope',
        ]));

        $refusal = $this->check->refuse(1, 9999);

        $this->assertNotNull($refusal);
        $this->assertSame($expected, $refusal->outcome);
        $this->assertSame('Nope', $refusal->reason);
    }

    /**
     * @return array<string, array{string, LineItemOutcome}>
     */
    public static function errorCodeProvider(): array
    {
        return [
            'single source, not enough units' => ['qty_available', LineItemOutcome::InsufficientStock],
            'single source, out of stock' => ['out_stock', LineItemOutcome::InsufficientStock],
            'single source, below minimum' => ['qty_min', LineItemOutcome::InvalidQuantity],
            'single source, over cart cap' => ['qty_max', LineItemOutcome::InvalidQuantity],
            'multi source, not enough units' => [
                'is_salable_with_reservations-not_enough_qty',
                LineItemOutcome::InsufficientStock,
            ],
            'multi source, over cart cap' => [
                'is_correct_qty-max_sale_qty',
                LineItemOutcome::InvalidQuantity,
            ],
            'unrecognised reason' => ['something_new', LineItemOutcome::InsufficientStock],
        ];
    }

    /**
     * @return void
     */
    public function testATranslatedMessageIsRendered(): void
    {
        $this->stockStateReturns(new DataObject([
            'has_error' => true,
            'error_code' => 'qty_available',
            'message' => __('The requested qty is not available'),
        ]));

        $refusal = $this->check->refuse(1, 9999);

        $this->assertNotNull($refusal);
        $this->assertSame('The requested qty is not available', $refusal->reason);
    }

    /**
     * @param DataObject $result
     * @return void
     */
    private function stockStateReturns(DataObject $result): void
    {
        // The interface types this int, so the stub has to be told to hand back the object anyway.
        $this->stockState->method('checkQuoteItemQty')->willReturn($result);
    }
}
