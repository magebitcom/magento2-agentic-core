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

use Magebit\AgenticCore\Api\Data\WebhookDeliveryInterface;

/**
 * Supplies the headers one delivery is sent with. Header names, signing scheme and correlation
 * identifiers are all caller vocabulary — one consumer signs an HMAC over the timestamp and body,
 * another uses RFC 9421 message signatures — so the queue asks rather than deciding.
 */
interface DeliveryHeadersProviderInterface
{
    /**
     * The delivery carries the event's own facts — its reference, body and when it was queued — while
     * the timestamp is this attempt's. Consumers need both and disagree about which belongs in which
     * header: one signs the attempt time for skew, another reports the occurrence time.
     *
     * @param WebhookDeliveryInterface $delivery
     * @param int $attemptTimestamp Unix seconds of this attempt
     * @return array<string, string>
     */
    public function getHeaders(WebhookDeliveryInterface $delivery, int $attemptTimestamp): array;
}
