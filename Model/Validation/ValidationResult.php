<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Validation;

/**
 * What a request body was found wrong with, if anything. Each entry is keyed by the dot-notation
 * path of the field it is about, so the caller can turn it into whatever its protocol reports.
 */
class ValidationResult
{
    /**
     * @param array<string, string> $errors Field path to error message
     */
    public function __construct(
        private readonly array $errors = []
    ) {
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string> Field path to error message
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
