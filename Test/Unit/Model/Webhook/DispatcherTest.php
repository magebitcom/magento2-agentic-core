<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Webhook;

use Magebit\AgenticCore\Api\Data\WebhookDeliveryInterface;
use Magebit\AgenticCore\Api\Data\WebhookDeliveryInterfaceFactory;
use Magebit\AgenticCore\Api\Webhook\DeliveryHeadersProviderInterface;
use Magebit\AgenticCore\Api\WebhookDeliveryRepositoryInterface;
use Magebit\AgenticCore\Model\Webhook\Delivery\Record;
use Magebit\AgenticCore\Model\Webhook\Dispatcher;
use Magebit\AgenticCore\Model\Webhook\Sender;
use Magebit\AgenticCore\Model\Webhook\SendOutcome;
use Magebit\AgenticCore\Model\Webhook\SendResult;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DispatcherTest extends TestCase
{
    private const NOW_TIMESTAMP = 1767225600;
    private const SCOPE = 'one';
    private const PAYLOAD = '{"a":1}';

    private WebhookDeliveryRepositoryInterface&MockObject $repository;

    private DeliveryHeadersProviderInterface&MockObject $headersProvider;

    private Sender&MockObject $sender;

    private Dispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(WebhookDeliveryRepositoryInterface::class);
        $this->sender = $this->createMock(Sender::class);

        $this->headersProvider = $this->createMock(DeliveryHeadersProviderInterface::class);
        $this->headersProvider->method('getHeaders')->willReturn(['Some-Signature' => 'abc']);

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW_TIMESTAMP);
        $dateTime->method('gmtDate')->willReturnCallback(
            static fn (string $format = 'Y-m-d H:i:s', $input = null): string
                => gmdate($format, is_int($input) ? $input : self::NOW_TIMESTAMP)
        );

        $this->dispatcher = new Dispatcher(
            $this->repository,
            $this->createMock(WebhookDeliveryInterfaceFactory::class),
            $this->sender,
            $this->headersProvider,
            $dateTime,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @param int $attemptsSoFar
     * @param int $expectedDelaySeconds
     * @return void
     * @dataProvider backoffProvider
     */
    public function testTheRetryDelayFollowsTheSchedule(int $attemptsSoFar, int $expectedDelaySeconds): void
    {
        $record = $this->pending($attemptsSoFar);
        $this->sender->method('send')->willReturn(new SendResult(SendOutcome::Retryable, 503));

        $this->dispatcher->dispatchDue(self::SCOPE);

        $this->assertSame(
            self::NOW_TIMESTAMP + $expectedDelaySeconds,
            strtotime((string) $record->getNextAttemptAt() . ' UTC')
        );
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function backoffProvider(): array
    {
        return [
            // A blip is retried at once rather than a minute later.
            'first retry is almost immediate' => [0, 1],
            'second waits a minute' => [1, 60],
            'third waits four' => [2, 240],
            // The schedule has run out by here, so the longest wait repeats. The attempt count must
            // stay below MAX_ATTEMPTS or the row is abandoned instead of rescheduled.
            'the wait is capped' => [6, Dispatcher::MAX_BACKOFF_SECONDS],
        ];
    }

    /**
     * @return void
     */
    public function testADeliveredRowIsNotAttemptedAgain(): void
    {
        $record = $this->pending(0);
        $this->sender->method('send')->willReturn(new SendResult(SendOutcome::Delivered, 200));

        $this->assertSame(1, $this->dispatcher->dispatchDue(self::SCOPE));
        $this->assertSame(WebhookDeliveryInterface::STATUS_DELIVERED, $record->getStatus());
    }

    /**
     * A rejected payload cannot become acceptable, so retrying it only burns attempts.
     *
     * @return void
     */
    public function testAPermanentRejectionFailsImmediately(): void
    {
        $record = $this->pending(0);
        $this->sender->method('send')->willReturn(new SendResult(SendOutcome::Permanent, 400, 'bad payload'));

        $this->dispatcher->dispatchDue(self::SCOPE);

        $this->assertSame(WebhookDeliveryInterface::STATUS_FAILED, $record->getStatus());
        $this->assertSame('bad payload', $record->getLastError());
    }

    /**
     * @return void
     */
    public function testTheAttemptCeilingIsTerminal(): void
    {
        $record = $this->pending(Dispatcher::MAX_ATTEMPTS - 1);
        $this->sender->method('send')->willReturn(new SendResult(SendOutcome::Retryable, 503));

        $this->dispatcher->dispatchDue(self::SCOPE);

        $this->assertSame(WebhookDeliveryInterface::STATUS_FAILED, $record->getStatus());
    }

    /**
     * The headers have to be built for the payload as sent, with the timestamp of the attempt rather
     * than of the enqueue — otherwise a row that waited out a backoff arrives outside the receiver's
     * skew window.
     *
     * @return void
     */
    public function testTheHeadersAreBuiltAtSendTime(): void
    {
        $this->pending(0);

        $provider = $this->createMock(DeliveryHeadersProviderInterface::class);
        $provider->expects($this->once())
            ->method('getHeaders')
            ->with($this->anything(), self::NOW_TIMESTAMP)
            ->willReturnCallback(
                function (WebhookDeliveryInterface $delivery, int $attemptTimestamp): array {
                    // The delivery carries the event; the timestamp is this attempt's.
                    $this->assertSame(self::PAYLOAD, $delivery->getPayload());
                    $this->assertSame('ref-1', $delivery->getReference());
                    $this->assertSame(self::NOW_TIMESTAMP, $attemptTimestamp);

                    return [];
                }
            );

        $this->dispatcherWith($provider)->dispatchDue(self::SCOPE);
    }

    /**
     * @return void
     */
    public function testNothingDueMeansNothingDelivered(): void
    {
        $this->repository->method('getDue')->willReturn([]);
        $this->sender->expects($this->never())->method('send');

        $this->assertSame(0, $this->dispatcher->dispatchDue(self::SCOPE));
    }

    /**
     * @param DeliveryHeadersProviderInterface $headersProvider
     * @return Dispatcher
     */
    private function dispatcherWith(DeliveryHeadersProviderInterface $headersProvider): Dispatcher
    {
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW_TIMESTAMP);
        $dateTime->method('gmtDate')->willReturnCallback(
            static fn (string $format = 'Y-m-d H:i:s', $input = null): string
                => gmdate($format, is_int($input) ? $input : self::NOW_TIMESTAMP)
        );

        $sender = $this->createMock(Sender::class);
        $sender->method('send')->willReturn(new SendResult(SendOutcome::Delivered, 200));

        return new Dispatcher(
            $this->repository,
            $this->createMock(WebhookDeliveryInterfaceFactory::class),
            $sender,
            $headersProvider,
            $dateTime,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * A real record rather than a mock, so the writes the dispatcher makes can be read back.
     *
     * @param int $attempts
     * @return Record
     */
    private function pending(int $attempts): Record
    {
        $record = $this->getMockBuilder(Record::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $record->setData(WebhookDeliveryInterface::SCOPE, self::SCOPE);
        $record->setData(WebhookDeliveryInterface::URL, 'https://example.test/hook');
        $record->setData(WebhookDeliveryInterface::PAYLOAD, self::PAYLOAD);
        $record->setData(WebhookDeliveryInterface::REFERENCE, 'ref-1');
        $record->setData(WebhookDeliveryInterface::ATTEMPTS, $attempts);
        $record->setData(WebhookDeliveryInterface::STATUS, WebhookDeliveryInterface::STATUS_PENDING);

        $this->repository->method('getDue')->willReturn([$record]);

        /** @var Record $record */
        return $record;
    }
}
