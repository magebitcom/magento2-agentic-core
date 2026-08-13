<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Total;

/**
 * Derives a human label for a total that has no title of its own.
 */
class TypeLabel
{
    /**
     * @param string $type Snake-cased total type
     * @return string
     */
    public function for(string $type): string
    {
        return ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * @param string|null $title The platform's own label, when it has one
     * @param string $type
     * @return string
     */
    public function orFallback(?string $title, string $type): string
    {
        $title = trim((string) $title);

        return $title !== '' ? $title : $this->for($type);
    }
}
