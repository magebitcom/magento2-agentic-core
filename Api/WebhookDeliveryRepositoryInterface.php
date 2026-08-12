<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Api;

use Magebit\AgenticCore\Api\Data\WebhookDeliveryInterface;
use Magento\Framework\Exception\CouldNotSaveException;

interface WebhookDeliveryRepositoryInterface
{
    /**
     * @param WebhookDeliveryInterface $delivery
     * @return WebhookDeliveryInterface
     * @throws CouldNotSaveException
     */
    public function save(WebhookDeliveryInterface $delivery): WebhookDeliveryInterface;

    /**
     * @param string $scope
     * @param string $now UTC datetime; rows due at or before it are returned
     * @param int $limit
     * @return WebhookDeliveryInterface[] Oldest due first
     */
    public function getDue(string $scope, string $now, int $limit): array;
}
