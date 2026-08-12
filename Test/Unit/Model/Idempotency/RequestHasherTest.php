<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Idempotency;

use Magebit\AgenticCore\Model\Idempotency\RequestHasher;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class RequestHasherTest extends TestCase
{
    private RequestHasher $hasher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->hasher = new RequestHasher();
    }

    /**
     * @return void
     */
    public function testTheSameRequestHashesTheSameWay(): void
    {
        $this->assertSame(
            $this->hasher->hash($this->request('POST', '/a', [], '{"q":1}')),
            $this->hasher->hash($this->request('POST', '/a', [], '{"q":1}'))
        );
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<string, string> $query
     * @param string $body
     * @return void
     * @dataProvider differenceProvider
     */
    public function testAnyDifferenceChangesTheHash(
        string $method,
        string $path,
        array $query,
        string $body
    ): void {
        $baseline = $this->hasher->hash($this->request('POST', '/a', [], '{"q":1}'));

        $this->assertNotSame($baseline, $this->hasher->hash($this->request($method, $path, $query, $body)));
    }

    /**
     * @return array<string, array{string, string, array<string, string>, string}>
     */
    public static function differenceProvider(): array
    {
        return [
            'method' => ['PUT', '/a', [], '{"q":1}'],
            'path' => ['POST', '/b', [], '{"q":1}'],
            'query' => ['POST', '/a', ['page' => '2'], '{"q":1}'],
            'body' => ['POST', '/a', [], '{"q":2}'],
        ];
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<string, string> $query
     * @param string $body
     * @return Http
     */
    private function request(string $method, string $path, array $query, string $body): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getPathInfo')->willReturn($path);
        $request->method('getQuery')->willReturn($query);
        $request->method('getContent')->willReturn($body);

        return $request;
    }
}
