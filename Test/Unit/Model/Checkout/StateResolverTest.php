<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Checkout;

use Magebit\AgenticCore\Model\Checkout\CheckoutState;
use Magebit\AgenticCore\Model\Checkout\StateResolver;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\TestCase;

class StateResolverTest extends TestCase
{
    private StateResolver $resolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resolver = new StateResolver();
    }

    /**
     * @param bool $isActive
     * @param bool $hasOrder
     * @param bool $isBlocked
     * @param CheckoutState $expected
     * @return void
     * @dataProvider stateProvider
     */
    public function testResolvesState(
        bool $isActive,
        bool $hasOrder,
        bool $isBlocked,
        CheckoutState $expected
    ): void {
        $quote = $this->createMock(CartInterface::class);
        $quote->method('getIsActive')->willReturn($isActive);

        $this->assertSame($expected, $this->resolver->resolve($quote, $hasOrder, $isBlocked));
    }

    /**
     * @return array<string, array{bool, bool, bool, CheckoutState}>
     */
    public static function stateProvider(): array
    {
        return [
            'clean and active is ready' => [true, false, false, CheckoutState::Ready],
            'blocked and active is incomplete' => [true, false, true, CheckoutState::Incomplete],
            'inactive without an order is canceled' => [false, false, false, CheckoutState::Canceled],
            'inactive with blockers is still canceled' => [false, false, true, CheckoutState::Canceled],
            // Placing an order deactivates the quote, so this is the state every completed checkout
            // is actually in — reading quote state first would report it canceled.
            'inactive with an order is completed' => [false, true, false, CheckoutState::Completed],
            'an order outranks outstanding blockers' => [false, true, true, CheckoutState::Completed],
            'an order outranks an active quote' => [true, true, false, CheckoutState::Completed],
        ];
    }
}
