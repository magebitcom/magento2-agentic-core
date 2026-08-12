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

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Psr\Log\LoggerInterface;

class Sender
{
    /**
     * Statuses worth another attempt. Every other 4xx is a rejection retrying cannot fix.
     */
    private const RETRYABLE_STATUSES = [408, 429];

    /**
     * @param CurlFactory $curlFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param string $url
     * @param string $payload
     * @param array<string, string> $headers Caller's headers; Content-Type and Content-Length are added
     * @param string $reference Caller's correlation id, for logging only
     * @return SendResult
     */
    public function send(string $url, string $payload, array $headers, string $reference): SendResult
    {
        /** @var Curl $curl */
        $curl = $this->curlFactory->create();

        try {
            foreach ($headers as $name => $value) {
                $curl->addHeader($name, $value);
            }

            // Transport concerns rather than caller vocabulary, so they are not the provider's to set.
            $curl->addHeader('Content-Type', 'application/json');
            $curl->addHeader('Content-Length', (string) strlen($payload));
            $curl->post($url, $payload);
        } catch (\Throwable $exception) {
            // Throwable rather than Exception: a TypeError inside the curl wrapper must not escape a
            // delivery attempt and abort the whole batch. No status at all is not a rejection.
            $this->logger->warning('Webhook transport failed', [
                'reference' => $reference,
                'exception' => $exception,
            ]);

            return new SendResult(SendOutcome::Retryable, 0, $exception->getMessage());
        }

        $status = $curl->getStatus();

        if ($status >= 200 && $status < 300) {
            return new SendResult(SendOutcome::Delivered, $status);
        }

        $outcome = $status >= 500 || in_array($status, self::RETRYABLE_STATUSES, true)
            ? SendOutcome::Retryable
            : SendOutcome::Permanent;

        return new SendResult($outcome, $status, (string) $curl->getBody());
    }
}
