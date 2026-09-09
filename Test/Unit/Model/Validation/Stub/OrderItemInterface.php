<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Validation\Stub;

/**
 * The element type of the stub order's list, so nested reporting can be exercised.
 */
interface OrderItemInterface
{
    public const KEY_SKU = 'sku';

    public const CONSTRAINTS = ['sku' => ['maxLength' => 4]];

    /**
     * @return string
     */
    public function getSku(): string;
}
