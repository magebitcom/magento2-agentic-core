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

class Route
{
    /**
     * @param string $path Pattern with {name} segments, no leading or trailing slash
     * @param string $method HTTP method, or '*' for any
     * @param string $controller
     * @param string $action
     */
    public function __construct(
        public readonly string $path,
        public readonly string $method,
        public readonly string $controller,
        public readonly string $action
    ) {
    }
}
