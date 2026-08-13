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
 * Generates P-256 signing pairs and the JWK a verifier needs to check them.
 */
class KeyPairGenerator
{
    public const CURVE = 'P-256';
    public const ALGORITHM = 'ES256';

    private const OPENSSL_CURVE = 'prime256v1';

    /**
     * @param string $keyId
     * @return KeyPair
     * @throws \RuntimeException
     */
    public function generate(string $keyId): KeyPair
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => self::OPENSSL_CURVE,
        ]);

        if ($key === false) {
            throw new \RuntimeException('A signing key pair could not be generated.');
        }

        $privateKeyPem = '';

        if (openssl_pkey_export($key, $privateKeyPem) === false) {
            throw new \RuntimeException('The generated private key could not be exported.');
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false || !isset($details['key'], $details['ec']['x'], $details['ec']['y'])) {
            throw new \RuntimeException('The generated key is missing its public coordinates.');
        }

        return new KeyPair(
            $keyId,
            $privateKeyPem,
            (string) $details['key'],
            $this->jwk($keyId, (string) $details['ec']['x'], (string) $details['ec']['y'])
        );
    }

    /**
     * @param string $keyId
     * @param string $x
     * @param string $y
     * @return array<string, string>
     */
    private function jwk(string $keyId, string $x, string $y): array
    {
        return [
            'kty' => 'EC',
            'crv' => self::CURVE,
            'x' => $this->base64Url($x),
            'y' => $this->base64Url($y),
            'kid' => $keyId,
            'use' => 'sig',
            'alg' => self::ALGORITHM,
        ];
    }

    /**
     * JWK coordinates are fixed-width and base64url without padding, per RFC 7518.
     *
     * @param string $coordinate
     * @return string
     */
    private function base64Url(string $coordinate): string
    {
        $padded = str_pad($coordinate, EcdsaP256Signer::COORDINATE_BYTES, "\x00", STR_PAD_LEFT);

        return rtrim(strtr(base64_encode($padded), '+/', '-_'), '=');
    }
}
