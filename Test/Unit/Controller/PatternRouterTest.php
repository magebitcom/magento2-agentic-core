<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Controller;

use Magebit\AgenticCore\Controller\PatternRouter;
use Magebit\AgenticCore\Controller\Route;
use Magebit\AgenticCore\Controller\RouteProviderInterface;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class PatternRouterTest extends TestCase
{
    private const MODULE = 'shared';

    /**
     * @param string $path
     * @param string $method
     * @param string|null $expectedAction
     * @param array<string, string> $expectedParams
     * @return void
     * @dataProvider matchProvider
     */
    public function testMatching(
        string $path,
        string $method,
        ?string $expectedAction,
        array $expectedParams = []
    ): void {
        $request = $this->createMock(Http::class);
        $request->method('getPathInfo')->willReturn($path);
        $request->method('getMethod')->willReturn($method);
        $request->method('getModuleName')->willReturn('');

        $actions = [];
        $request->method('setActionName')->willReturnCallback(
            function (string $action) use (&$actions, $request) {
                $actions[] = $action;
                return $request;
            }
        );

        $params = [];
        $request->method('setParam')->willReturnCallback(
            function (string $key, $value) use (&$params, $request) {
                $params[$key] = $value;
                return $request;
            }
        );

        $result = $this->router()->match($request);

        if ($expectedAction === null) {
            $this->assertNull($result);
            return;
        }

        $this->assertNotNull($result);
        $this->assertSame([$expectedAction], $actions);
        $this->assertSame($expectedParams, $params);
    }

    /**
     * @return array<string, array{string, string, string|null, array<string, string>}>
     */
    public static function matchProvider(): array
    {
        return [
            'exact path and method' => ['/checkout/sessions', 'POST', 'create', []],
            'leading and trailing slashes are ignored' => ['/checkout/sessions/', 'POST', 'create', []],
            'a captured segment becomes a param' => [
                '/checkout/sessions/abc123',
                'GET',
                'read',
                ['session_id' => 'abc123'],
            ],
            'the same path with another method reaches another action' => [
                '/checkout/sessions/abc123',
                'POST',
                'write',
                ['session_id' => 'abc123'],
            ],
            'a wildcard method matches anything' => ['/health', 'DELETE', 'health', []],
            'an unknown path does not match' => ['/somewhere/else', 'GET', null, []],
            'a known path with an unrouted method does not match' => ['/checkout/sessions', 'DELETE', null, []],
            // One caller matched on any shared segment, so this reached the session controller.
            'a path sharing one segment does not match' => ['/checkout', 'POST', null, []],
            // A captured segment stops at the separator, so a deeper path is not swallowed.
            'a captured segment does not span a slash' => ['/checkout/sessions/abc123/extra', 'GET', null, []],
        ];
    }

    /**
     * @return void
     */
    public function testAnAlreadyRoutedRequestIsLeftAlone(): void
    {
        $request = $this->createMock(Http::class);
        $request->method('getModuleName')->willReturn(self::MODULE);
        $request->method('getPathInfo')->willReturn('/checkout/sessions');
        $request->method('getMethod')->willReturn('POST');

        $this->assertNull($this->router()->match($request));
    }

    /**
     * @return PatternRouter
     */
    private function router(): PatternRouter
    {
        $provider = $this->createMock(RouteProviderInterface::class);
        $provider->method('getRoutes')->willReturn([
            new Route('checkout/sessions', 'POST', 'sessions', 'create'),
            new Route('checkout/sessions/{session_id}', 'GET', 'sessions', 'read'),
            new Route('checkout/sessions/{session_id}', 'POST', 'sessions', 'write'),
            new Route('health', '*', 'status', 'health'),
        ]);

        $actionFactory = $this->createMock(ActionFactory::class);
        $actionFactory->method('create')->willReturn($this->createMock(Forward::class));

        return new PatternRouter($actionFactory, $provider, self::MODULE);
    }
}
