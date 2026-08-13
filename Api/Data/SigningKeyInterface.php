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
 * A key pair used to sign outbound messages. The private half is encrypted at rest.
 */
interface SigningKeyInterface
{
    public const ENTITY_ID = 'entity_id';
    public const SCOPE = 'scope';
    public const STORE_ID = 'store_id';
    public const KID = 'kid';
    public const PUBLIC_JWK = 'public_jwk';
    public const PRIVATE_KEY = 'private_key';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';

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
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return string|null
     */
    public function getKid(): ?string;

    /**
     * @param string $kid
     * @return $this
     */
    public function setKid(string $kid): self;

    /**
     * @return array<string, string> Empty when the stored value is unreadable
     */
    public function getPublicJwk(): array;

    /**
     * @param array<string, string> $jwk
     * @return $this
     */
    public function setPublicJwk(array $jwk): self;

    /**
     * @return string|null Decrypted PEM
     */
    public function getPrivateKeyPem(): ?string;

    /**
     * @param string $pem
     * @return $this
     */
    public function setPrivateKeyPem(string $pem): self;

    /**
     * @return bool
     */
    public function isActive(): bool;

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
