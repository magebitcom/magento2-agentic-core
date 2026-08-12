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

class PersonName
{
    /**
     * Splits a single name field into first and last. A name with no separator is a first name —
     * refusing it loses a deliverable address over a field this cannot validate.
     *
     * @param string|null $name
     * @return array{0: string|null, 1: string|null}
     */
    public static function split(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return [null, null];
        }

        // preg_split on \s+ rather than explode(' ') so a double space does not yield an empty token.
        $parts = preg_split('/\s+/', $name, 2) ?: [];
        $first = $parts[0] ?? null;
        $last = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : null;

        return [$first, $last];
    }
}
