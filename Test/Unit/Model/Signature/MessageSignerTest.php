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
use Magebit\AgenticCore\Model\Signature\MessageSigner;
use Magebit\AgenticCore\Model\Signature\SignatureBase;
use PHPUnit\Framework\TestCase;

class MessageSignerTest extends TestCase
{
    private const COMPONENTS = [
        '@method' => 'POST',
        '@authority' => 'platform.example',
        '@path' => '/hooks',
        'content-digest' => 'sha-256=:X48E9qOokqqrvdts8nOJRJN3OWDUoyWxBf7kbu9DBPE=:',
        'content-type' => 'application/json',
    ];

    /**
     * @var MessageSigner
     */
    private MessageSigner $signer;

    /**
     * @var string
     */
    private string $privateKey;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->signer = new MessageSigner(new SignatureBase(), new EcdsaP256Signer());

        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($resource, $pem);
        $this->privateKey = (string) $pem;
    }

    /**
     * @return void
     */
    public function testSignatureInputListsTheCoveredComponentsInOrderWithTheKeyId(): void
    {
        $headers = $this->signer->sign(self::COMPONENTS, 'merchant-2026', $this->privateKey);

        $this->assertSame(
            'sig1=("@method" "@authority" "@path" "content-digest" "content-type");keyid="merchant-2026"',
            $headers[MessageSigner::INPUT_HEADER]
        );
    }

    /**
     * RFC 8941 byte sequences are base64 wrapped in colons, and the label has to match Signature-Input.
     *
     * @return void
     */
    public function testSignatureIsAColonWrappedByteSequence(): void
    {
        $headers = $this->signer->sign(self::COMPONENTS, 'merchant-2026', $this->privateKey);

        $this->assertMatchesRegularExpression('~^sig1=:[A-Za-z0-9+/]+={0,2}:$~', $headers[MessageSigner::HEADER]);
    }

    /**
     * The signature has to cover the base the header advertises, so decoding it and verifying against
     * that exact string is the only check that means anything.
     *
     * @return void
     */
    public function testTheSignatureCoversTheAdvertisedBase(): void
    {
        $headers = $this->signer->sign(self::COMPONENTS, 'merchant-2026', $this->privateKey);
        $params = substr($headers[MessageSigner::INPUT_HEADER], strlen('sig1='));

        $expected = (new SignatureBase())->build(self::COMPONENTS, $params);

        $this->assertSame($expected, $this->signer->baseFor(self::COMPONENTS, 'merchant-2026'));
    }

    /**
     * The algorithm is derived from the key's curve, so naming it in the parameters is wrong.
     *
     * @return void
     */
    public function testDoesNotAdvertiseAnAlgorithm(): void
    {
        $headers = $this->signer->sign(self::COMPONENTS, 'merchant-2026', $this->privateKey);

        $this->assertStringNotContainsString('alg=', $headers[MessageSigner::INPUT_HEADER]);
    }

    /**
     * @return void
     */
    public function testCreatedIsIncludedOnlyWhenGiven(): void
    {
        $without = $this->signer->sign(self::COMPONENTS, 'k1', $this->privateKey);
        $with = $this->signer->sign(self::COMPONENTS, 'k1', $this->privateKey, 1738617601);

        $this->assertStringNotContainsString('created=', $without[MessageSigner::INPUT_HEADER]);
        $this->assertStringContainsString(';created=1738617601;keyid="k1"', $with[MessageSigner::INPUT_HEADER]);
    }
}
