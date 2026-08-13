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
 * Turns a set of covered components into the Signature-Input and Signature headers.
 */
class MessageSigner
{
    public const HEADER = 'Signature';
    public const INPUT_HEADER = 'Signature-Input';

    /**
     * One signature per message, so the label is fixed.
     */
    public const LABEL = 'sig1';

    /**
     * @param SignatureBase $signatureBase
     * @param EcdsaP256Signer $signer
     */
    public function __construct(
        private readonly SignatureBase $signatureBase,
        private readonly EcdsaP256Signer $signer
    ) {
    }

    /**
     * @param array<string, string> $components Component name to value, in the order they are signed
     * @param string $keyId The `kid` of the key, as published in the profile
     * @param string $privateKeyPem
     * @param int|null $created Unix seconds, omitted when null
     * @return array<string, string> The two headers, ready to send
     * @throws \RuntimeException
     */
    public function sign(array $components, string $keyId, string $privateKeyPem, ?int $created = null): array
    {
        $params = $this->params($components, $keyId, $created);
        $signature = $this->signer->sign($this->signatureBase->build($components, $params), $privateKeyPem);

        return [
            self::INPUT_HEADER => self::LABEL . '=' . $params,
            self::HEADER => self::LABEL . '=:' . base64_encode($signature) . ':',
        ];
    }

    /**
     * The bytes a verifier will reconstruct. Exposed so a test or a conformance check can compare
     * against what was actually signed.
     *
     * @param array<string, string> $components
     * @param string $keyId
     * @param int|null $created
     * @return string
     */
    public function baseFor(array $components, string $keyId, ?int $created = null): string
    {
        return $this->signatureBase->build($components, $this->params($components, $keyId, $created));
    }

    /**
     * @param array<string, string> $components
     * @param string $keyId
     * @param int|null $created
     * @return string
     */
    private function params(array $components, string $keyId, ?int $created): string
    {
        $names = array_map(
            static fn (string $name): string => sprintf('"%s"', strtolower($name)),
            array_keys($components)
        );

        $params = sprintf('(%s)', implode(' ', $names));

        if ($created !== null) {
            $params .= ';created=' . $created;
        }

        // No `alg`: the curve in the published key determines it, and repeating it here lets a caller
        // claim one algorithm while signing with another.
        return $params . sprintf(';keyid="%s"', $keyId);
    }
}
