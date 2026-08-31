<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Block\Adminhtml\Order\View;

use Magebit\AgenticCore\Api\OrderLinkRepositoryInterface;
use Magebit\AgenticCore\Block\Adminhtml\Order\View\PlacedBy;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class PlacedByTest extends TestCase
{
    private const LABELS = ['alpha' => 'Placed over Alpha', 'beta' => 'Placed over Beta'];

    /**
     * @return void
     */
    public function testReportsTheLabelForTheScopeThatPlacedTheOrder(): void
    {
        $this->assertSame('Placed over Alpha', $this->block(scope: 'alpha')->getLabel());
        $this->assertSame('Placed over Beta', $this->block(scope: 'beta')->getLabel());
    }

    /**
     * Most orders come from the storefront and have no link, so the badge renders nothing at all rather
     * than an empty one.
     *
     * @return void
     */
    public function testAStorefrontOrderHasNoLabel(): void
    {
        $this->assertNull($this->block(scope: null)->getLabel());
    }

    /**
     * A scope no module supplied a label for is left blank rather than shown raw, so an internal
     * identifier never leaks into the admin UI.
     *
     * @return void
     */
    public function testAnUnlabelledScopeHasNoLabel(): void
    {
        $this->assertNull($this->block(scope: 'something_else')->getLabel());
    }

    /**
     * @return void
     */
    public function testNoOrderInRegistryHasNoLabel(): void
    {
        $this->assertNull($this->block(scope: 'alpha', hasOrder: false)->getLabel());
    }

    /**
     * @param string|null $scope
     * @param bool $hasOrder
     * @return PlacedBy
     */
    private function block(?string $scope, bool $hasOrder = true): PlacedBy
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityId'])
            ->getMock();
        $order->method('getEntityId')->willReturn(42);

        $registry = $this->createMock(Registry::class);
        $registry->method('registry')->willReturn($hasOrder ? $order : null);

        $repository = $this->createMock(OrderLinkRepositoryInterface::class);
        $repository->method('findScope')->willReturn($scope);

        return new PlacedBy($this->createMock(Context::class), $registry, $repository, self::LABELS);
    }
}
