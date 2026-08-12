<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Webhook;

use Magebit\AgenticCore\Model\Webhook\Signer;
use PHPUnit\Framework\TestCase;

class SignerTest extends TestCase
{
    private const PAYLOAD = '{"type":"order.created"}';
    private const SECRET = 'shhh';
    private const TIMESTAMP = 1767225600;

    /**
     * Fixed against an independently computed HMAC rather than recomputed the way the code does:
     * php -r 'echo hash_hmac("sha256", "1767225600.{\"type\":\"order.created\"}", "shhh");'
     */
    private const EXPECTED_DIGEST = 'a8b81dadade31d6a17d3e4c24cb9b0951a9799334a0d0b598e7c7c19f09c4f23';

    /**
     * @return void
     */
    public function testTheSignatureCarriesTheTimestampAndTheDigest(): void
    {
        $signature = (new Signer())->sign(self::PAYLOAD, self::TIMESTAMP, self::SECRET);

        $this->assertSame('t=' . self::TIMESTAMP . ',v1=' . self::EXPECTED_DIGEST, $signature);
    }

    /**
     * The timestamp is part of the signed material, not merely carried beside it — otherwise a
     * captured request replays forever under a rewritten `t`.
     *
     * @return void
     */
    public function testTheTimestampIsSigned(): void
    {
        $signer = new Signer();

        $this->assertNotSame(
            $this->digestOf($signer->sign(self::PAYLOAD, self::TIMESTAMP, self::SECRET)),
            $this->digestOf($signer->sign(self::PAYLOAD, self::TIMESTAMP + 1, self::SECRET))
        );
    }

    /**
     * @return void
     */
    public function testTheDigestIsSixtyFourHexCharacters(): void
    {
        $digest = $this->digestOf((new Signer())->sign(self::PAYLOAD, self::TIMESTAMP, self::SECRET));

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $digest);
    }

    /**
     * @return void
     */
    public function testTheVersionLabelIsConfigurable(): void
    {
        $signature = (new Signer('v2'))->sign(self::PAYLOAD, self::TIMESTAMP, self::SECRET);

        $this->assertStringContainsString(',v2=', $signature);
    }

    /**
     * @param string $signature
     * @return string
     */
    private function digestOf(string $signature): string
    {
        return substr($signature, (int) strpos($signature, '=', (int) strpos($signature, ',')) + 1);
    }
}
