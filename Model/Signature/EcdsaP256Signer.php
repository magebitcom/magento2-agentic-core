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
 * Signs with ECDSA P-256 / SHA-256 and returns the raw r||s form that HTTP message signatures use.
 */
class EcdsaP256Signer
{
    /**
     * Half the signature: P-256 r and s are 32 bytes each.
     */
    public const COORDINATE_BYTES = 32;

    private const DER_SEQUENCE = 0x30;
    private const DER_INTEGER = 0x02;

    /**
     * @param string $message Bytes to sign, exactly as built
     * @param string $privateKeyPem
     * @return string 64 raw bytes: r then s, each left-padded to 32
     * @throws \RuntimeException
     */
    public function sign(string $message, string $privateKeyPem): string
    {
        $key = openssl_pkey_get_private($privateKeyPem);

        if ($key === false) {
            throw new \RuntimeException('The signing key could not be read.');
        }

        $details = openssl_pkey_get_details($key);

        if (($details['type'] ?? null) !== OPENSSL_KEYTYPE_EC) {
            throw new \RuntimeException('HTTP message signatures require an elliptic curve key.');
        }

        $der = '';

        if (openssl_sign($message, $der, $key, OPENSSL_ALGO_SHA256) === false) {
            throw new \RuntimeException('The message could not be signed.');
        }

        return $this->rawFromDer($der);
    }

    /**
     * OpenSSL returns DER, which the spec forbids on the wire, so the two integers are unpacked and
     * re-emitted at fixed width.
     *
     * @param string $der
     * @return string
     * @throws \RuntimeException
     */
    private function rawFromDer(string $der): string
    {
        $offset = 0;
        $this->readTag($der, $offset, self::DER_SEQUENCE);

        return $this->coordinate($this->readInteger($der, $offset))
            . $this->coordinate($this->readInteger($der, $offset));
    }

    /**
     * @param string $der
     * @param int $offset
     * @param int $expected
     * @return int Length of the value that follows
     * @throws \RuntimeException
     */
    private function readTag(string $der, int &$offset, int $expected): int
    {
        if (!isset($der[$offset], $der[$offset + 1]) || ord($der[$offset]) !== $expected) {
            throw new \RuntimeException('The signature is not the expected DER structure.');
        }

        $length = ord($der[$offset + 1]);
        $offset += 2;

        // Lengths above 127 use the long form. A P-256 signature never reaches it, so meeting one means
        // this is not the structure we think it is.
        if ($length > 0x7F) {
            throw new \RuntimeException('The signature has an unexpected DER length.');
        }

        return $length;
    }

    /**
     * @param string $der
     * @param int $offset
     * @return string
     * @throws \RuntimeException
     */
    private function readInteger(string $der, int &$offset): string
    {
        $length = $this->readTag($der, $offset, self::DER_INTEGER);
        $value = substr($der, $offset, $length);

        if (strlen($value) !== $length) {
            throw new \RuntimeException('The signature ended before its contents did.');
        }

        $offset += $length;

        return $value;
    }

    /**
     * DER stores a signed integer, so a leading zero may be padding and a short value may be missing
     * leading zeroes. Both have to be normalised to exactly 32 bytes.
     *
     * @param string $value
     * @return string
     * @throws \RuntimeException
     */
    private function coordinate(string $value): string
    {
        $value = ltrim($value, "\x00");

        if (strlen($value) > self::COORDINATE_BYTES) {
            throw new \RuntimeException('The signature coordinate is too large for P-256.');
        }

        return str_pad($value, self::COORDINATE_BYTES, "\x00", STR_PAD_LEFT);
    }
}
