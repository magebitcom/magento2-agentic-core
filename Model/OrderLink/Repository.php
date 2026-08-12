<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\OrderLink;

use Magebit\AgenticCore\Api\Data\OrderLinkInterface;
use Magebit\AgenticCore\Api\OrderLinkRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class Repository implements OrderLinkRepositoryInterface
{
    /**
     * @param ResourceModel $resourceModel
     * @param LinkFactory $linkFactory
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceModel $resourceModel,
        private readonly LinkFactory $linkFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function link(
        string $scope,
        string $sessionId,
        ?int $quoteId,
        int $orderId
    ): OrderLinkInterface
    {
        $link = $this->find($scope, $sessionId) ?? $this->linkFactory->create();
        $link->setScope($scope);
        $link->setSessionId($sessionId);
        $link->setQuoteId($quoteId);
        $link->setOrderId($orderId);

        try {
            /** @var Link $link */
            $this->resourceModel->save($link);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new CouldNotSaveException(__('Could not link the session to its order.'), $exception);
        }

        return $link;
    }

    /**
     * @inheritDoc
     */
    public function findOrderId(string $scope, string $sessionId): ?int
    {
        return $this->find($scope, $sessionId)?->getOrderId();
    }

    /**
     * @inheritDoc
     */
    public function findSessionId(string $scope, int $orderId): ?string
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(OrderLinkInterface::SCOPE, $scope);
        $collection->addFieldToFilter(OrderLinkInterface::ORDER_ID, ['eq' => $orderId]);
        $collection->setPageSize(1);

        /** @var OrderLinkInterface|null $link */
        $link = $collection->getFirstItem();

        return $link && $link->getEntityId() ? $link->getSessionId() : null;
    }

    /**
     * The identity is two columns, so this reads through the collection rather than load().
     *
     * @param string $scope
     * @param string $sessionId
     * @return OrderLinkInterface|null
     */
    private function find(string $scope, string $sessionId): ?OrderLinkInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(OrderLinkInterface::SCOPE, $scope);
        $collection->addFieldToFilter(OrderLinkInterface::SESSION_ID, $sessionId);
        $collection->setPageSize(1);

        /** @var OrderLinkInterface|null $link */
        $link = $collection->getFirstItem();

        return $link && $link->getEntityId() ? $link : null;
    }
}
