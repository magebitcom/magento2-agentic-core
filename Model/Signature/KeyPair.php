<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Signature;

/**
 * A freshly generated signing key pair, before anything decides where to store it.
 */
class KeyPair
{
    /**
     * @param string $keyId
     * @param string $privateKeyPem
     * @param string $publicKeyPem
     * @param array<string, string> $publicJwk
     */
    public function __construct(
        private readonly string $keyId,
        private readonly string $privateKeyPem,
        private readonly string $publicKeyPem,
        private readonly array $publicJwk
    ) {
    }

    /**
     * @return string
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * @return string
     */
    public function getPrivateKeyPem(): string
    {
        return $this->privateKeyPem;
    }

    /**
     * @return string
     */
    public function getPublicKeyPem(): string
    {
        return $this->publicKeyPem;
    }

    /**
     * @return array<string, string>
     */
    public function getPublicJwk(): array
    {
        return $this->publicJwk;
    }
}
