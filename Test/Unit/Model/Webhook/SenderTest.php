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

use Magebit\AgenticCore\Model\Webhook\Sender;
use Magebit\AgenticCore\Model\Webhook\SendOutcome;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SenderTest extends TestCase
{
    /**
     * @param int $status
     * @param SendOutcome $expected
     * @return void
     * @dataProvider statusProvider
     */
    public function testTheResponseStatusIsClassified(int $status, SendOutcome $expected): void
    {
        $curl = $this->createMock(Curl::class);
        $curl->method('getStatus')->willReturn($status);
        $curl->method('getBody')->willReturn('');

        $result = $this->senderWith($curl)->send('https://example.test/hook', '{}', 't=1,v1=x', 'ref-1');

        $this->assertSame($expected, $result->outcome);
        $this->assertSame($status, $result->status);
    }

    /**
     * @return array<string, array{int, SendOutcome}>
     */
    public static function statusProvider(): array
    {
        return [
            '200 is delivered' => [200, SendOutcome::Delivered],
            '204 is delivered' => [204, SendOutcome::Delivered],
            '400 will never succeed' => [400, SendOutcome::Permanent],
            '404 will never succeed' => [404, SendOutcome::Permanent],
            '408 is worth retrying' => [408, SendOutcome::Retryable],
            '429 is worth retrying' => [429, SendOutcome::Retryable],
            '500 is worth retrying' => [500, SendOutcome::Retryable],
            '503 is worth retrying' => [503, SendOutcome::Retryable],
        ];
    }

    /**
     * A transport failure has no status at all and must not be mistaken for a rejection.
     *
     * @return void
     */
    public function testATransportFailureIsRetryable(): void
    {
        $curl = $this->createMock(Curl::class);
        $curl->method('post')->willThrowException(new \Exception('Could not resolve host'));

        $result = $this->senderWith($curl)->send('https://example.test/hook', '{}', 't=1,v1=x', 'ref-1');

        $this->assertSame(SendOutcome::Retryable, $result->outcome);
        $this->assertSame(0, $result->status);
        $this->assertSame('Could not resolve host', $result->error);
    }

    /**
     * @return void
     */
    public function testTheSignatureAndReferenceAreSentAsHeaders(): void
    {
        $headers = [];
        $curl = $this->createMock(Curl::class);
        $curl->method('getStatus')->willReturn(200);
        $curl->method('addHeader')->willReturnCallback(
            function (string $name, $value) use (&$headers): void {
                $headers[$name] = (string) $value;
            }
        );

        $this->senderWith($curl)->send('https://example.test/hook', '{"a":1}', 't=1,v1=x', 'ref-1');

        $this->assertSame('t=1,v1=x', $headers['Merchant-Signature']);
        $this->assertSame('ref-1', $headers['Request-Id']);
        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertSame('7', $headers['Content-Length']);
    }

    /**
     * @param Curl $curl
     * @return Sender
     */
    private function senderWith(Curl $curl): Sender
    {
        $curlFactory = $this->createMock(CurlFactory::class);
        $curlFactory->method('create')->willReturn($curl);

        return new Sender($curlFactory, $this->createMock(LoggerInterface::class));
    }
}
