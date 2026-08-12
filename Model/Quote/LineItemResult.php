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

class LineItemResult
{
    /**
     * @param int $index Position in the submitted list, so a caller can build a path back to it
     * @param string $sku
     * @param LineItemOutcome $outcome
     * @param string|null $reason Present only for Rejected
     */
    public function __construct(
        public readonly int $index,
        public readonly string $sku,
        public readonly LineItemOutcome $outcome,
        public readonly ?string $reason = null
    ) {
    }
}
