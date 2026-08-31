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
 * Records on the order how it was placed, so a merchant reading the order in admin can see it came from
 * an agent rather than the storefront. The caller supplies the label; this class names no protocol.
 */
class PlacementNote
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
     * @param string $note What to record, e.g. the name of the protocol that placed the order
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
            // Saved here: addCommentToStatusHistory only stages the entry in memory, and nothing else
            // on the placement path saves the order again.
            $order->addCommentToStatusHistory($note, false, false);
            $this->orderRepository->save($order);
        } catch (\Throwable $exception) {
            // The order is already placed. A note that cannot be written must not undo it.
            $this->logger->error('Could not record how an order was placed', [
                'exception' => $exception,
                'order_id' => $order->getIncrementId(),
            ]);
        }
    }
}
