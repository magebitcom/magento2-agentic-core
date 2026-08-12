<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Api\Data;

/**
 * Records which order a checkout session produced, so placement is a fact rather than an inference.
 */
interface OrderLinkInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SCOPE = 'scope';
    public const SESSION_ID = 'session_id';
    public const QUOTE_ID = 'quote_id';
    public const ORDER_ID = 'order_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * @return string|null
     */
    public function getScope(): ?string;

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope): self;

    /**
     * @return string|null
     */
    public function getSessionId(): ?string;

    /**
     * @param string $sessionId
     * @return $this
     */
    public function setSessionId(string $sessionId): self;

    /**
     * @return int|null
     */
    public function getQuoteId(): ?int;

    /**
     * Nullable because quote cleanup removes quotes whose orders are still live.
     *
     * @param int|null $quoteId
     * @return $this
     */
    public function setQuoteId(?int $quoteId): self;

    /**
     * Null until an order is actually placed.
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
