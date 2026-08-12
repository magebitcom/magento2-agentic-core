<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Webhook;

class Signer
{
    /**
     * @param string $version Label the receiver looks for beside the digest
     */
    public function __construct(
        private readonly string $version = 'v1'
    ) {
    }

    /**
     * The receiver checks the timestamp for replay before verifying the digest, so the timestamp is
     * both signed and carried rather than derived from either side's clock.
     *
     * @param string $payload Raw request body, exactly as it will be sent
     * @param int $timestamp Unix seconds
     * @param string $secret
     * @return string Signature in the `t=<unix>,<version>=<64 hex>` convention
     */
    public function sign(string $payload, int $timestamp, string $secret): string
    {
        $digest = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return 't=' . $timestamp . ',' . $this->version . '=' . $digest;
    }
}
