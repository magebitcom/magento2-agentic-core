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
 * One idempotent request record. The scope partitions rows between callers sharing the table.
 */
interface IdempotencyRecordInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SCOPE = 'scope';
    public const IDEMPOTENCY_KEY = 'idempotency_key';
    public const REQUEST_HASH = 'request_hash';
    public const RESPONSE_STATUS = 'response_status';
    public const RESPONSE_BODY = 'response_body';
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
    public function getIdempotencyKey(): ?string;

    /**
     * @param string $idempotencyKey
     * @return $this
     */
    public function setIdempotencyKey(string $idempotencyKey): self;

    /**
     * @return string|null
     */
    public function getRequestHash(): ?string;

    /**
     * @param string $requestHash
     * @return $this
     */
    public function setRequestHash(string $requestHash): self;

    /**
     * Null while the claim is in flight.
     *
     * @return int|null
     */
    public function getResponseStatus(): ?int;

    /**
     * @param int $responseStatus
     * @return $this
     */
    public function setResponseStatus(int $responseStatus): self;

    /**
     * @return string|null
     */
    public function getResponseBody(): ?string;

    /**
     * @param string $responseBody
     * @return $this
     */
    public function setResponseBody(string $responseBody): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
