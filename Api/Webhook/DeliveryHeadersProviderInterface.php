<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Api\Webhook;

/**
 * Supplies the headers one delivery is sent with. Header names, signing scheme and correlation
 * identifiers are all caller vocabulary — one consumer signs an HMAC over the timestamp and body,
 * another uses RFC 9421 message signatures — so the queue asks rather than deciding.
 */
interface DeliveryHeadersProviderInterface
{
    /**
     * @param string $scope
     * @param string $payload Raw body, exactly as it will be sent
     * @param int $timestamp Unix seconds of this attempt, not of the enqueue
     * @param string $reference Caller's correlation id for the delivery
     * @return array<string, string>
     */
    public function getHeaders(string $scope, string $payload, int $timestamp, string $reference): array;
}
