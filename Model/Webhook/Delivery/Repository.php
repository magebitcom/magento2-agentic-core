<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Webhook\Delivery;

use Magebit\AgenticCore\Api\Data\WebhookDeliveryInterface;
use Magebit\AgenticCore\Api\WebhookDeliveryRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class Repository implements WebhookDeliveryRepositoryInterface
{
    /**
     * @param ResourceModel $resourceModel
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResourceModel $resourceModel,
        private readonly CollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(WebhookDeliveryInterface $delivery): WebhookDeliveryInterface
    {
        try {
            /** @var Record $delivery */
            $this->resourceModel->save($delivery);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new CouldNotSaveException(
                __('Could not save the webhook delivery: %1', $exception->getMessage()),
                $exception
            );
        }

        return $delivery;
    }

    /**
     * @inheritDoc
     */
    public function getDue(string $scope, string $now, int $limit): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(WebhookDeliveryInterface::SCOPE, $scope);
        $collection->addFieldToFilter(
            WebhookDeliveryInterface::STATUS,
            WebhookDeliveryInterface::STATUS_PENDING
        );
        $collection->addFieldToFilter(WebhookDeliveryInterface::NEXT_ATTEMPT_AT, ['lteq' => $now]);
        $collection->setOrder(WebhookDeliveryInterface::NEXT_ATTEMPT_AT, 'ASC');
        $collection->setPageSize($limit);

        /** @var WebhookDeliveryInterface[] $items */
        $items = array_values($collection->getItems());

        return $items;
    }
}
