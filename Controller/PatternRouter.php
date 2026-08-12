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

use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

/**
 * Matches a request against anchored path patterns, capturing named segments as request params.
 */
class PatternRouter implements RouterInterface
{
    /**
     * @param ActionFactory $actionFactory
     * @param RouteProviderInterface $routeProvider
     * @param string $moduleName Module the matched request is forwarded to
     */
    public function __construct(
        private readonly ActionFactory $actionFactory,
        private readonly RouteProviderInterface $routeProvider,
        private readonly string $moduleName
    ) {
    }

    /**
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        /** @var Http $request */
        if ($request->getModuleName() === $this->moduleName) {
            return null;
        }

        $path = trim($request->getPathInfo(), '/');
        $method = $request->getMethod();

        foreach ($this->routeProvider->getRoutes() as $route) {
            if (!$this->matchesMethod($route->method, $method)) {
                continue;
            }

            $params = $this->matchPath($route->path, $path);

            if ($params === false) {
                continue;
            }

            $request->setModuleName($this->moduleName);
            $request->setControllerName($route->controller);
            $request->setActionName($route->action);

            foreach ($params as $key => $value) {
                $request->setParam($key, $value);
            }

            // @phpstan-ignore arguments.count
            return $this->actionFactory->create(Forward::class, ['request' => $request]);
        }

        return null;
    }

    /**
     * @param string $pattern
     * @param string $path
     * @return array<string, string>|false
     */
    private function matchPath(string $pattern, string $path): array|false
    {
        if (!preg_match($this->patternToRegex($pattern), $path, $matches)) {
            return false;
        }

        $params = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * The pattern is anchored at both ends, so a request sharing a prefix or a single segment with a
     * route does not match it.
     *
     * @param string $pattern
     * @return string
     */
    private function patternToRegex(string $pattern): string
    {
        $regex = preg_quote($pattern, '#');
        // preg_quote leaves { } as \{ \} — capture the name between them. [^/]+ is what keeps a
        // captured segment from spanning a separator.
        $regex = (string) preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $regex);

        return '#^' . $regex . '$#';
    }

    /**
     * @param string $routeMethod
     * @param string $requestMethod
     * @return bool
     */
    private function matchesMethod(string $routeMethod, string $requestMethod): bool
    {
        if ($routeMethod === '*') {
            return true;
        }

        return strcasecmp($routeMethod, $requestMethod) === 0;
    }
}
