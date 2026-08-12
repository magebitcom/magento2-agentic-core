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
use Magento\Framework\Model\AbstractModel;

class Record extends AbstractModel implements WebhookDeliveryInterface
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
    public function setScope(string $scope): WebhookDeliveryInterface
    {
        return $this->setData(self::SCOPE, $scope);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): ?string
    {
        return $this->stringOrNull(self::URL);
    }

    /**
     * @inheritDoc
     */
    public function setUrl(string $url): WebhookDeliveryInterface
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): ?string
    {
        return $this->stringOrNull(self::PAYLOAD);
    }

    /**
     * @inheritDoc
     */
    public function setPayload(string $payload): WebhookDeliveryInterface
    {
        return $this->setData(self::PAYLOAD, $payload);
    }

    /**
     * @inheritDoc
     */
    public function getReference(): ?string
    {
        return $this->stringOrNull(self::REFERENCE);
    }

    /**
     * @inheritDoc
     */
    public function setReference(string $reference): WebhookDeliveryInterface
    {
        return $this->setData(self::REFERENCE, $reference);
    }

    /**
     * @inheritDoc
     */
    public function getAttempts(): ?int
    {
        return $this->intOrNull(self::ATTEMPTS);
    }

    /**
     * @inheritDoc
     */
    public function setAttempts(int $attempts): WebhookDeliveryInterface
    {
        return $this->setData(self::ATTEMPTS, $attempts);
    }

    /**
     * @inheritDoc
     */
    public function getNextAttemptAt(): ?string
    {
        return $this->stringOrNull(self::NEXT_ATTEMPT_AT);
    }

    /**
     * @inheritDoc
     */
    public function setNextAttemptAt(string $nextAttemptAt): WebhookDeliveryInterface
    {
        return $this->setData(self::NEXT_ATTEMPT_AT, $nextAttemptAt);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->stringOrNull(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): WebhookDeliveryInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getLastError(): ?string
    {
        return $this->stringOrNull(self::LAST_ERROR);
    }

    /**
     * @inheritDoc
     */
    public function setLastError(string $lastError): WebhookDeliveryInterface
    {
        return $this->setData(self::LAST_ERROR, $lastError);
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
