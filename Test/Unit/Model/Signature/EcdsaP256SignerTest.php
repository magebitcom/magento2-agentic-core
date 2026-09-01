<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Signature;

use Magebit\AgenticCore\Model\Signature\EcdsaP256Signer;
use PHPUnit\Framework\TestCase;

class EcdsaP256SignerTest extends TestCase
{
    private const MESSAGE = '"@method": POST' . "\n" . '"@signature-params": ("@method");keyid="k1"';

    /**
     * @var EcdsaP256Signer
     */
    private EcdsaP256Signer $signer;

    /**
     * @var string
     */
    private string $privateKey;

    /**
     * @var string
     */
    private string $publicKey;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->signer = new EcdsaP256Signer();

        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($resource, $pem);
        $this->privateKey = (string) $pem;
        $this->publicKey = (string) openssl_pkey_get_details($resource)['key'];
    }

    /**
     * P-256 is 32 bytes of r and 32 of s. The length is also what proves the signature is not DER,
     * which OpenSSL emits by default and the spec forbids: DER wraps the pair in a SEQUENCE and never
     * comes to 64 bytes. A separate test used to check the leading SEQUENCE byte instead and failed
     * about one run in 256, because `r` is random and starts with 0x30 as often as with anything else.
     *
     * @return void
     */
    public function testProducesSixtyFourRawBytes(): void
    {
        $this->assertSame(64, strlen($this->signer->sign(self::MESSAGE, $this->privateKey)));
    }

    /**
     * Re-assembling DER from r and s must verify against the public key, or the halves are swapped
     * or misaligned in a way a length check cannot see.
     *
     * @return void
     */
    public function testTheSignatureVerifiesAgainstThePublicKey(): void
    {
        $signature = $this->signer->sign(self::MESSAGE, $this->privateKey);

        $this->assertSame(1, openssl_verify(
            self::MESSAGE,
            $this->derFrom($signature),
            $this->publicKey,
            OPENSSL_ALGO_SHA256
        ));
    }

    /**
     * @return void
     */
    public function testADifferentMessageDoesNotVerify(): void
    {
        $signature = $this->signer->sign(self::MESSAGE, $this->privateKey);

        $this->assertSame(0, openssl_verify(
            self::MESSAGE . 'tampered',
            $this->derFrom($signature),
            $this->publicKey,
            OPENSSL_ALGO_SHA256
        ));
    }

    /**
     * @return void
     */
    public function testRejectsAKeyThatIsNotEcdsa(): void
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        openssl_pkey_export($resource, $rsa);

        $this->expectException(\RuntimeException::class);

        $this->signer->sign(self::MESSAGE, (string) $rsa);
    }

    /**
     * @param string $raw
     * @return string
     */
    private function derFrom(string $raw): string
    {
        $r = $this->derInteger(substr($raw, 0, 32));
        $s = $this->derInteger(substr($raw, 32, 32));
        $sequence = $r . $s;

        return "\x30" . chr(strlen($sequence)) . $sequence;
    }

    /**
     * @param string $value
     * @return string
     */
    private function derInteger(string $value): string
    {
        $value = ltrim($value, "\x00");

        if ($value === '' || (ord($value[0]) & 0x80) !== 0) {
            $value = "\x00" . $value;
        }

        return "\x02" . chr(strlen($value)) . $value;
    }
}
