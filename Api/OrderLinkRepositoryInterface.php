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

use Magebit\AgenticCore\Api\Data\OrderLinkInterface;
use Magento\Framework\Exception\CouldNotSaveException;

interface OrderLinkRepositoryInterface
{
    /**
     * An upsert: a retried completion must update the existing row rather than raise on the unique key.
     *
     * @param string $scope
     * @param string $sessionId
     * @param int|null $quoteId
     * @param int $orderId
     * @return OrderLinkInterface
     * @throws CouldNotSaveException
     */
    public function link(
        string $scope,
        string $sessionId,
        ?int $quoteId,
        int $orderId
    ): OrderLinkInterface;

    /**
     * @param string $scope
     * @param string $sessionId
     * @return int|null Null when the session produced no order.
     */
    public function findOrderId(string $scope, string $sessionId): ?int;

    /**
     * @param string $scope
     * @param int $orderId
     * @return string|null Null when the order came from no session in this scope.
     */
    public function findSessionId(string $scope, int $orderId): ?string;
}
