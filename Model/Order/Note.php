<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Writes an internal note onto an order, so whoever reads the order in admin can see what an agent did.
 * The caller supplies the wording; this class names no protocol.
 */
class Note
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param OrderInterface $order
     * @param string $note What to record, e.g. that an agent placed the order
     * @return void
     */
    public function add(OrderInterface $order, string $note): void
    {
        if (!$order instanceof Order) {
            return;
        }

        try {
            // Not customer-notified and not visible on the storefront: this is an internal record for
            // whoever is looking at the order, not a message to the buyer.
            // Saved here: addCommentToStatusHistory only stages the entry in memory, and the caller
            // has no other reason to save the order again.
            $order->addCommentToStatusHistory($note, false, false);
            $this->orderRepository->save($order);
        } catch (\Throwable $exception) {
            // The order already exists. A note that cannot be written must not undo what it describes.
            $this->logger->error('Could not write a note onto an order', [
                'exception' => $exception,
                'order_id' => $order->getIncrementId(),
            ]);
        }
    }
}
