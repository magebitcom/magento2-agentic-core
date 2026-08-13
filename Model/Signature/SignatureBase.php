<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Signature;

/**
 * Builds the RFC 9421 signature base — the exact bytes that get signed.
 */
class SignatureBase
{
    public const PARAMS_COMPONENT = '@signature-params';

    /**
     * @param array<string, string> $components Component name to value, in the order they are signed
     * @param string $signatureParams The inner list and its parameters, without the label
     * @return string
     * @throws \InvalidArgumentException
     */
    public function build(array $components, string $signatureParams): string
    {
        if ($components === []) {
            throw new \InvalidArgumentException('A signature must cover at least one component.');
        }

        $lines = [];

        foreach ($components as $name => $value) {
            $lines[] = sprintf('"%s": %s', strtolower($name), $value);
        }

        // The params line closes the base and carries no newline after it: one extra byte here and
        // every signature we produce fails verification.
        $lines[] = sprintf('"%s": %s', self::PARAMS_COMPONENT, $signatureParams);

        return implode("\n", $lines);
    }
}
