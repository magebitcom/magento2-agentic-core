<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Idempotency;

use Magebit\AgenticCore\Model\Idempotency\ResourceModel;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\DuplicateException;
use PHPUnit\Framework\TestCase;

class ResourceModelClaimTest extends TestCase
{
    /**
     * @return void
     */
    public function testClaimReportsFalseWhenTheKeyIsAlreadyHeld(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('insert')->willThrowException(new DuplicateException('duplicate'));

        $resource = $this->resourceModelWith($connection);

        $this->assertFalse($resource->claim('one', 'k-1', 'hash-1'));
    }

    /**
     * @return void
     */
    public function testClaimReportsTrueWhenTheInsertSucceeds(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->once())->method('insert');

        $resource = $this->resourceModelWith($connection);

        $this->assertTrue($resource->claim('one', 'k-1', 'hash-1'));
    }

    /**
     * @return void
     */
    public function testReclaimReportsWhetherARowWasTakenOver(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('update')->willReturn(0);

        $resource = $this->resourceModelWith($connection);

        $this->assertFalse($resource->reclaimAbandoned('one', 'k-1', 'hash-1', '2026-01-01 00:00:00'));
    }

    /**
     * The resource model reaches its connection through the framework's resource registry, which a
     * unit test cannot build; the connection is injected in its place.
     *
     * @param AdapterInterface $connection
     * @return ResourceModel
     */
    private function resourceModelWith(AdapterInterface $connection): ResourceModel
    {
        $resource = $this->getMockBuilder(ResourceModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getMainTable'])
            ->getMock();
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('agentic_idempotency');

        /** @var ResourceModel $resource */
        return $resource;
    }
}
