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

use Magebit\AgenticCore\Api\Data\WebhookDeliveryInterface;
use Magebit\AgenticCore\Api\Data\WebhookDeliveryInterfaceFactory;
use Magebit\AgenticCore\Api\Webhook\DeliveryHeadersProviderInterface;
use Magebit\AgenticCore\Api\WebhookDeliveryRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Queues deliveries and attempts the due ones, backing off between attempts.
 */
class Dispatcher
{
    /**
     * Attempts after which a delivery is abandoned.
     */
    public const MAX_ATTEMPTS = 8;

    /**
     * How long to wait before each retry, first retry first. The first is almost immediate, because a
     * failed delivery is usually a momentary blip and a receiver that is already back should not be
     * made to wait a minute. After that the waits grow hard, so one that is genuinely down is not
     * hammered. The whole sequence spans a little over two hours, and MAX_ATTEMPTS ends it.
     */
    public const BACKOFF_SECONDS = [1, 60, 240, 540, 960, 1500, 1800];

    /**
     * The longest wait in the schedule.
     */
    public const MAX_BACKOFF_SECONDS = 1800;

    /**
     * @param WebhookDeliveryRepositoryInterface $repository
     * @param WebhookDeliveryInterfaceFactory $deliveryFactory
     * @param Sender $sender
     * @param DeliveryHeadersProviderInterface $headersProvider
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly WebhookDeliveryRepositoryInterface $repository,
        private readonly WebhookDeliveryInterfaceFactory $deliveryFactory,
        private readonly Sender $sender,
        private readonly DeliveryHeadersProviderInterface $headersProvider,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param string $scope
     * @param string $url
     * @param string $payload
     * @param string $reference
     * @return void
     * @throws CouldNotSaveException
     */
    public function enqueue(string $scope, string $url, string $payload, string $reference): void
    {
        /** @var WebhookDeliveryInterface $delivery */
        $delivery = $this->deliveryFactory->create();
        $delivery->setScope($scope);
        $delivery->setUrl($url);
        $delivery->setPayload($payload);
        $delivery->setReference($reference);
        $delivery->setAttempts(0);
        $delivery->setStatus(WebhookDeliveryInterface::STATUS_PENDING);
        $delivery->setNextAttemptAt($this->dateTime->gmtDate('Y-m-d H:i:s'));

        $this->repository->save($delivery);
    }

    /**
     * @param string $scope
     * @param int $limit
     * @return int Number delivered.
     */
    public function dispatchDue(string $scope, int $limit = 50): int
    {
        $now = $this->dateTime->gmtTimestamp();
        $due = $this->repository->getDue($scope, $this->dateTime->gmtDate('Y-m-d H:i:s', $now), $limit);
        $delivered = 0;

        foreach ($due as $delivery) {
            if ($this->attempt($delivery, $scope, $now)) {
                $delivered++;
            }
        }

        return $delivered;
    }

    /**
     * @param WebhookDeliveryInterface $delivery
     * @param string $scope
     * @param int $now Unix seconds; also the timestamp that gets signed
     * @return bool
     * @throws CouldNotSaveException
     */
    private function attempt(WebhookDeliveryInterface $delivery, string $scope, int $now): bool
    {
        $payload = (string) $delivery->getPayload();
        $reference = (string) $delivery->getReference();

        $result = $this->sender->send(
            (string) $delivery->getUrl(),
            $payload,
            // Built per attempt, not per enqueue: a row that waited out a backoff would otherwise
            // arrive with a timestamp outside the receiver's skew window.
            $this->headersProvider->getHeaders($delivery, $now),
            $reference
        );

        $attempts = (int) $delivery->getAttempts() + 1;
        $delivery->setAttempts($attempts);

        if ($result->outcome === SendOutcome::Delivered) {
            $delivery->setStatus(WebhookDeliveryInterface::STATUS_DELIVERED);
            $this->repository->save($delivery);

            return true;
        }

        if ($result->error !== null) {
            $delivery->setLastError($result->error);
        }

        if ($result->outcome === SendOutcome::Permanent || $attempts >= self::MAX_ATTEMPTS) {
            $delivery->setStatus(WebhookDeliveryInterface::STATUS_FAILED);
            $this->logger->error('Webhook abandoned', [
                'reference' => $delivery->getReference(),
                'attempts' => $attempts,
                'status' => $result->status,
            ]);
        } else {
            $delivery->setNextAttemptAt(
                $this->dateTime->gmtDate('Y-m-d H:i:s', $now + $this->backoff($attempts))
            );
        }

        $this->repository->save($delivery);

        return false;
    }

    /**
     * @param int $attempts How many attempts have been made, including the one that just failed
     * @return int
     */
    private function backoff(int $attempts): int
    {
        $index = min(max($attempts, 1) - 1, count(self::BACKOFF_SECONDS) - 1);

        return self::BACKOFF_SECONDS[$index];
    }
}
