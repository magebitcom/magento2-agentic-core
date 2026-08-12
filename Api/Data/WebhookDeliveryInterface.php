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
 * One queued outbound delivery. The signing secret is deliberately absent: it is resolved at send
 * time, so a queue row is not a credential at rest.
 */
interface WebhookDeliveryInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SCOPE = 'scope';
    public const URL = 'url';
    public const PAYLOAD = 'payload';
    public const REFERENCE = 'reference';
    public const ATTEMPTS = 'attempts';
    public const NEXT_ATTEMPT_AT = 'next_attempt_at';
    public const STATUS = 'status';
    public const LAST_ERROR = 'last_error';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

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
    public function getUrl(): ?string;

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self;

    /**
     * @return string|null
     */
    public function getPayload(): ?string;

    /**
     * @param string $payload
     * @return $this
     */
    public function setPayload(string $payload): self;

    /**
     * @return string|null
     */
    public function getReference(): ?string;

    /**
     * @param string $reference
     * @return $this
     */
    public function setReference(string $reference): self;

    /**
     * @return int|null
     */
    public function getAttempts(): ?int;

    /**
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts): self;

    /**
     * @return string|null
     */
    public function getNextAttemptAt(): ?string;

    /**
     * @param string $nextAttemptAt
     * @return $this
     */
    public function setNextAttemptAt(string $nextAttemptAt): self;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * @return string|null
     */
    public function getLastError(): ?string;

    /**
     * @param string $lastError
     * @return $this
     */
    public function setLastError(string $lastError): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
