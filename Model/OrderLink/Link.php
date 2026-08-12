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
use Magento\Framework\Model\AbstractModel;

class Link extends AbstractModel implements OrderLinkInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): ?int
    {
        return $this->intOrNull(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function getScope(): ?string
    {
        return $this->stringOrNull(self::SCOPE);
    }

    /**
     * @inheritDoc
     */
    public function setScope(string $scope): OrderLinkInterface
    {
        return $this->setData(self::SCOPE, $scope);
    }

    /**
     * @inheritDoc
     */
    public function getSessionId(): ?string
    {
        return $this->stringOrNull(self::SESSION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSessionId(string $sessionId): OrderLinkInterface
    {
        return $this->setData(self::SESSION_ID, $sessionId);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId(): ?int
    {
        return $this->intOrNull(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId(?int $quoteId): OrderLinkInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): ?int
    {
        return $this->intOrNull(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId): OrderLinkInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->stringOrNull(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->stringOrNull(self::UPDATED_AT);
    }

    /**
     * getData() is typed mixed; a column that is set holds a scalar.
     *
     * @param string $key
     * @return string|null
     */
    private function stringOrNull(string $key): ?string
    {
        $value = $this->getData($key);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param string $key
     * @return int|null
     */
    private function intOrNull(string $key): ?int
    {
        $value = $this->getData($key);

        return is_numeric($value) ? (int) $value : null;
    }
}
