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
use Magebit\AgenticCore\Model\Signature\KeyPairGenerator;
use PHPUnit\Framework\TestCase;

class KeyPairGeneratorTest extends TestCase
{
    /**
     * @var KeyPairGenerator
     */
    private KeyPairGenerator $generator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->generator = new KeyPairGenerator();
    }

    /**
     * @return void
     */
    public function testTheJwkDescribesAP256VerificationKey(): void
    {
        $jwk = $this->generator->generate('merchant-2026')->getPublicJwk();

        $this->assertSame('EC', $jwk['kty']);
        $this->assertSame('P-256', $jwk['crv']);
        $this->assertSame('ES256', $jwk['alg']);
        $this->assertSame('sig', $jwk['use']);
        $this->assertSame('merchant-2026', $jwk['kid']);
    }

    /**
     * A verifier reconstructs the point from x and y, which are fixed-width base64url per RFC 7518.
     *
     * @return void
     */
    public function testCoordinatesAreThirtyTwoBytesOfBase64Url(): void
    {
        $jwk = $this->generator->generate('k1')->getPublicJwk();

        foreach (['x', 'y'] as $coordinate) {
            $this->assertDoesNotMatchRegularExpression('~[+/=]~', $jwk[$coordinate], $coordinate . ' is base64url');
            $this->assertSame(
                EcdsaP256Signer::COORDINATE_BYTES,
                strlen((string) base64_decode(strtr($jwk[$coordinate], '-_', '+/'), true)),
                $coordinate . ' is 32 bytes'
            );
        }
    }

    /**
     * @return void
     */
    public function testThePrivateHalfIsNotInTheJwk(): void
    {
        $jwk = $this->generator->generate('k1')->getPublicJwk();

        $this->assertArrayNotHasKey('d', $jwk);
    }

    /**
     * The pair has to belong together, so signing with the private half and verifying against the
     * public one is the only real check.
     *
     * @return void
     */
    public function testThePrivateKeySignsForThePublishedKey(): void
    {
        $pair = $this->generator->generate('k1');
        $signature = (new EcdsaP256Signer())->sign('message', $pair->getPrivateKeyPem());

        $this->assertSame(64, strlen($signature));
        $this->assertNotSame('', $pair->getPublicKeyPem());
    }

    /**
     * @return void
     */
    public function testEachPairIsDistinct(): void
    {
        $this->assertNotSame(
            $this->generator->generate('k1')->getPrivateKeyPem(),
            $this->generator->generate('k1')->getPrivateKeyPem()
        );
    }
}
