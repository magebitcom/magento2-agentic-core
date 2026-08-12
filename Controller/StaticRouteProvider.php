<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Controller;

/**
 * Turns a di.xml array of routes into value objects, skipping any element that is not a complete
 * string map — a malformed route dropped is better than a fatal during routing.
 */
class StaticRouteProvider implements RouteProviderInterface
{
    private const REQUIRED_KEYS = ['path', 'method', 'controller', 'action'];

    /**
     * @param array<string, array<string, string>> $routes
     */
    public function __construct(
        private readonly array $routes = []
    ) {
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        $routes = [];

        foreach ($this->routes as $route) {
            if (!$this->isComplete($route)) {
                continue;
            }

            $routes[] = new Route($route['path'], $route['method'], $route['controller'], $route['action']);
        }

        return $routes;
    }

    /**
     * @param mixed $route
     * @return bool
     */
    private function isComplete(mixed $route): bool
    {
        if (!is_array($route)) {
            return false;
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (!isset($route[$key]) || !is_string($route[$key])) {
                return false;
            }
        }

        return true;
    }
}
