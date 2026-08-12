<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Idempotency;

use Magebit\AgenticCore\Api\Data\IdempotencyRecordInterface;
use Magento\Framework\Model\AbstractModel;

class Record extends AbstractModel implements IdempotencyRecordInterface
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
    public function setScope(string $scope): IdempotencyRecordInterface
    {
        return $this->setData(self::SCOPE, $scope);
    }

    /**
     * @inheritDoc
     */
    public function getIdempotencyKey(): ?string
    {
        return $this->stringOrNull(self::IDEMPOTENCY_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setIdempotencyKey(string $idempotencyKey): IdempotencyRecordInterface
    {
        return $this->setData(self::IDEMPOTENCY_KEY, $idempotencyKey);
    }

    /**
     * @inheritDoc
     */
    public function getRequestHash(): ?string
    {
        return $this->stringOrNull(self::REQUEST_HASH);
    }

    /**
     * @inheritDoc
     */
    public function setRequestHash(string $requestHash): IdempotencyRecordInterface
    {
        return $this->setData(self::REQUEST_HASH, $requestHash);
    }

    /**
     * @inheritDoc
     */
    public function getResponseStatus(): ?int
    {
        return $this->intOrNull(self::RESPONSE_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setResponseStatus(int $responseStatus): IdempotencyRecordInterface
    {
        return $this->setData(self::RESPONSE_STATUS, $responseStatus);
    }

    /**
     * @inheritDoc
     */
    public function getResponseBody(): ?string
    {
        return $this->stringOrNull(self::RESPONSE_BODY);
    }

    /**
     * @inheritDoc
     */
    public function setResponseBody(string $responseBody): IdempotencyRecordInterface
    {
        return $this->setData(self::RESPONSE_BODY, $responseBody);
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
     * An empty string reads as absent rather than as zero.
     *
     * @param string $key
     * @return int|null
     */
    private function intOrNull(string $key): ?int
    {
        $value = $this->getData($key);

        return is_numeric($value) ? (int) $value : null;
    }
}
