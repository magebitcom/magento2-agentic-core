<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\OrderLink;

use Magebit\AgenticCore\Api\Data\OrderLinkInterface;
use Magebit\AgenticCore\Model\OrderLink\Collection;
use Magebit\AgenticCore\Model\OrderLink\CollectionFactory;
use Magebit\AgenticCore\Model\OrderLink\Link;
use Magebit\AgenticCore\Model\OrderLink\LinkFactory;
use Magebit\AgenticCore\Model\OrderLink\Repository;
use Magebit\AgenticCore\Model\OrderLink\ResourceModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The collection is driven by an in-memory store keyed on scope and session id, so the scope
 * isolation assertion is real rather than a mock expectation.
 */
class RepositoryTest extends TestCase
{
    /**
     * @var array<string, Link>
     */
    private array $store = [];

    private int $nextId = 1;

    private ResourceModel&MockObject $resource;

    private Repository $repository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->store = [];
        $this->nextId = 1;

        $this->resource = $this->getMockBuilder(ResourceModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $this->resource->method('save')->willReturnCallback(
            function (Link $link): ResourceModel {
                if (!$link->getEntityId()) {
                    $link->setData(OrderLinkInterface::ENTITY_ID, $this->nextId++);
                }

                $this->store[$this->key(
                    (string) $link->getScope(),
                    (string) $link->getSessionId()
                )] = $link;

                return $this->resource;
            }
        );

        $linkFactory = $this->createMock(LinkFactory::class);
        $linkFactory->method('create')->willReturnCallback(fn (): Link => $this->emptyLink());

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturnCallback(fn (): Collection => $this->collection());

        $this->repository = new Repository(
            $this->resource,
            $linkFactory,
            $collectionFactory,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * A retried completion must update the existing row rather than raise on the unique key.
     *
     * @return void
     */
    public function testLinkingTwiceUpdatesTheSameRow(): void
    {
        $this->resource->expects($this->exactly(2))->method('save');

        $this->repository->link('one', 's-1', 10, 100);
        $this->repository->link('one', 's-1', 10, 101);

        $this->assertCount(1, $this->store);
        $this->assertSame(101, $this->repository->findOrderId('one', 's-1'));
    }

    /**
     * @return void
     */
    public function testAnUnlinkedSessionHasNoOrder(): void
    {
        $this->assertNull($this->repository->findOrderId('one', 'never-completed'));
    }

    /**
     * Two callers may issue the same session id; neither may read the other's link.
     *
     * @return void
     */
    public function testScopesDoNotSeeEachOthersLinks(): void
    {
        $this->repository->link('one', 's-1', 10, 100);

        $this->assertNull($this->repository->findOrderId('two', 's-1'));
        $this->assertSame(100, $this->repository->findOrderId('one', 's-1'));
    }

    /**
     * The refund path needs the reverse lookup: which session produced this order.
     *
     * @return void
     */
    public function testTheSessionCanBeFoundFromItsOrder(): void
    {
        $this->repository->link('one', 's-1', 10, 100);

        $this->assertSame('s-1', $this->repository->findSessionId('one', 100));
        $this->assertNull($this->repository->findSessionId('one', 999));
        $this->assertNull($this->repository->findSessionId('two', 100));
    }

    /**
     * @param string $scope
     * @param string $sessionId
     * @return string
     */
    private function key(string $scope, string $sessionId): string
    {
        return $scope . '|' . $sessionId;
    }

    /**
     * @return Link
     */
    private function emptyLink(): Link
    {
        $link = $this->getMockBuilder(Link::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        /** @var Link $link */
        return $link;
    }

    /**
     * A collection that resolves its filters against the in-memory store.
     *
     * @return Collection
     */
    private function collection(): Collection
    {
        $filters = [];

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->getMock();

        $collection->method('addFieldToFilter')->willReturnCallback(
            function (string $field, $condition) use (&$filters, $collection): Collection {
                $filters[$field] = $condition;
                return $collection;
            }
        );
        $collection->method('setPageSize')->willReturn($collection);
        $collection->method('getFirstItem')->willReturnCallback(
            function () use (&$filters): Link {
                foreach ($this->store as $link) {
                    if ($this->matchesFilters($link, $filters)) {
                        return $link;
                    }
                }

                return $this->emptyLink();
            }
        );

        /** @var Collection $collection */
        return $collection;
    }

    /**
     * @param Link $link
     * @param array<string, mixed> $filters
     * @return bool
     */
    private function matchesFilters(Link $link, array $filters): bool
    {
        foreach ($filters as $field => $expected) {
            // The repository passes scalars for string columns and ['eq' => n] for numeric ones.
            $value = is_array($expected) ? reset($expected) : $expected;

            if ((string) $link->getData($field) !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
