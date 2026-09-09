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
 * Checks one value against the rules the schema declared for it. The generated interfaces carry
 * those rules in a CONSTRAINTS constant, so nothing here has to read the schema files.
 */
class ConstraintChecker
{
    /**
     * Name of the constant the generated interfaces carry their rules in.
     */
    public const CONSTANT = 'CONSTRAINTS';

    /**
     * Patterns for the `format` values the specifications actually use. A format with no pattern
     * here is descriptive only and is left alone rather than guessed at.
     */
    private const FORMAT_PATTERNS = [
        'email' => '/^[^@\s]+@[^@\s.]+\.[^@\s]+$/',
        'uuid' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        'date-time' => '/^\d{4}-\d{2}-\d{2}[Tt]\d{2}:\d{2}:\d{2}(\.\d+)?([Zz]|[+-]\d{2}:\d{2})$/',
    ];

    /**
     * @param string $field Field name, used in the message
     * @param mixed $value Value the request carried
     * @param array<string, mixed> $rules Schema keywords for this field
     * @return string|null The first rule the value breaks, or null when it breaks none
     */
    public function check(string $field, mixed $value, array $rules): ?string
    {
        if (is_string($value)) {
            return $this->checkString($field, $value, $rules);
        }

        if (is_int($value) || is_float($value)) {
            return $this->checkNumber($field, (float) $value, $rules);
        }

        if (is_array($value)) {
            return $this->checkList($field, $value, $rules);
        }

        return null;
    }

    /**
     * @param string $field
     * @param string $value
     * @param array<string, mixed> $rules
     * @return string|null
     */
    private function checkString(string $field, string $value, array $rules): ?string
    {
        $length = mb_strlen($value);
        $minLength = $rules['minLength'] ?? null;
        $maxLength = $rules['maxLength'] ?? null;

        if (is_int($minLength) && $length < $minLength) {
            return sprintf('Field "%s" must be at least %d characters long', $field, $minLength);
        }

        if (is_int($maxLength) && $length > $maxLength) {
            return sprintf('Field "%s" must be at most %d characters long', $field, $maxLength);
        }

        $pattern = $rules['pattern'] ?? null;

        if (is_string($pattern) && preg_match($this->toRegex($pattern), $value) !== 1) {
            return sprintf('Field "%s" does not match the expected format', $field);
        }

        return $this->checkFormat($field, $value, $rules);
    }

    /**
     * An empty string is left to the pattern and length rules, since a field that is genuinely
     * optional is often sent empty rather than omitted.
     *
     * @param string $field
     * @param string $value
     * @param array<string, mixed> $rules
     * @return string|null
     */
    private function checkFormat(string $field, string $value, array $rules): ?string
    {
        $format = $rules['format'] ?? null;

        if (!is_string($format) || $value === '') {
            return null;
        }

        if ($format === 'uri') {
            return filter_var($value, FILTER_VALIDATE_URL) === false
                ? sprintf('Field "%s" must be a URL', $field)
                : null;
        }

        $pattern = self::FORMAT_PATTERNS[$format] ?? null;

        if ($pattern === null || preg_match($pattern, $value) === 1) {
            return null;
        }

        return sprintf('Field "%s" must be a valid %s', $field, str_replace('-', ' ', $format));
    }

    /**
     * @param string $field
     * @param float $value
     * @param array<string, mixed> $rules
     * @return string|null
     */
    private function checkNumber(string $field, float $value, array $rules): ?string
    {
        $minimum = $rules['minimum'] ?? null;
        $maximum = $rules['maximum'] ?? null;
        $exclusiveMinimum = $rules['exclusiveMinimum'] ?? null;
        $exclusiveMaximum = $rules['exclusiveMaximum'] ?? null;

        if (is_numeric($minimum) && $value < (float) $minimum) {
            return sprintf('Field "%s" must be %s or more', $field, $minimum);
        }

        if (is_numeric($maximum) && $value > (float) $maximum) {
            return sprintf('Field "%s" must be %s or less', $field, $maximum);
        }

        if (is_numeric($exclusiveMinimum) && $value <= (float) $exclusiveMinimum) {
            return sprintf('Field "%s" must be more than %s', $field, $exclusiveMinimum);
        }

        if (is_numeric($exclusiveMaximum) && $value >= (float) $exclusiveMaximum) {
            return sprintf('Field "%s" must be less than %s', $field, $exclusiveMaximum);
        }

        return null;
    }

    /**
     * @param string $field
     * @param array<mixed> $value
     * @param array<string, mixed> $rules
     * @return string|null
     */
    private function checkList(string $field, array $value, array $rules): ?string
    {
        $count = count($value);
        $minItems = $rules['minItems'] ?? null;
        $maxItems = $rules['maxItems'] ?? null;

        if (is_int($minItems) && $count < $minItems) {
            return sprintf('Field "%s" must have at least %d item(s)', $field, $minItems);
        }

        if (is_int($maxItems) && $count > $maxItems) {
            return sprintf('Field "%s" must have at most %d item(s)', $field, $maxItems);
        }

        return null;
    }

    /**
     * Schema patterns are unanchored and carry no delimiters, so the delimiter has to be one the
     * pattern cannot contain itself.
     *
     * @param string $pattern
     * @return string
     */
    private function toRegex(string $pattern): string
    {
        return '#' . str_replace('#', '\#', $pattern) . '#';
    }
}
