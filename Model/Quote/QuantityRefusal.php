<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Quote;

/**
 * Why the store will not sell the number of units that were asked for.
 */
class QuantityRefusal
{
    /**
     * @param LineItemOutcome $outcome
     * @param string $reason Magento's own wording, which names the actual limit
     */
    public function __construct(
        public readonly LineItemOutcome $outcome,
        public readonly string $reason
    ) {
    }
}
