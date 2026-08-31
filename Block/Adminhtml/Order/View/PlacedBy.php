<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Block\Adminhtml\Order\View;

use Magebit\AgenticCore\Api\OrderLinkRepositoryInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

/**
 * Shows on the admin order view how the order was placed. The label comes from configuration, so the
 * base names no protocol; a module adds its own scope and label in di.xml.
 */
class PlacedBy extends Template
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param OrderLinkRepositoryInterface $orderLinkRepository
     * @param array<string, string> $labels Scope to what a merchant should read
     * @param array<mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly OrderLinkRepositoryInterface $orderLinkRepository,
        private readonly array $labels = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Null for a storefront order, which is most of them, and the badge is then not rendered at all.
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        $order = $this->registry->registry('current_order');

        if (!$order instanceof Order || !is_numeric($order->getEntityId())) {
            return null;
        }

        $scope = $this->orderLinkRepository->findScope((int) $order->getEntityId());

        return $scope === null ? null : ($this->labels[$scope] ?? null);
    }
}
