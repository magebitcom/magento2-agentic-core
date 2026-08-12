<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Idempotency;

use Magebit\AgenticCore\Api\Data\IdempotencyRecordInterface;
use Magebit\AgenticCore\Api\Data\IdempotencyRecordInterfaceFactory;
use Magebit\AgenticCore\Api\IdempotencyRecordRepositoryInterface;
use Magebit\AgenticCore\Model\Idempotency\ClaimOutcome;
use Magebit\AgenticCore\Model\Idempotency\Coordinator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoordinatorTest extends TestCase
{
    private const SCOPE = 'one';
    private const KEY = 'k-1';
    private const HASH = 'hash-1';

    private IdempotencyRecordRepositoryInterface&MockObject $repository;

    private Coordinator $coordinator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(IdempotencyRecordRepositoryInterface::class);

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(1000);
        $dateTime->method('gmtDate')->willReturn('2026-01-01 00:00:00');

        $this->coordinator = new Coordinator(
            $this->repository,
            $this->createMock(IdempotencyRecordInterfaceFactory::class),
            $dateTime
        );
    }

    /**
     * @return void
     */
    public function testAWonClaimLetsTheCallerProceed(): void
    {
        $this->repository->method('claim')->willReturn(true);

        $result = $this->coordinator->claim(self::SCOPE, self::KEY, self::HASH);

        $this->assertSame(ClaimOutcome::Claimed, $result->outcome);
    }

    /**
     * @return void
     */
    public function testAStoredResponseIsReplayed(): void
    {
        $this->repository->method('claim')->willReturn(false);
        $this->repository->method('getByKey')->willReturn($this->record(self::HASH, 201));

        $result = $this->coordinator->claim(self::SCOPE, self::KEY, self::HASH);

        $this->assertSame(ClaimOutcome::Replay, $result->outcome);
        $this->assertSame(201, $result->record?->getResponseStatus());
    }

    /**
     * @return void
     */
    public function testADifferentBodyUnderTheSameKeyConflicts(): void
    {
        $this->repository->method('claim')->willReturn(false);
        $this->repository->method('getByKey')->willReturn($this->record('a-different-hash', 201));

        $result = $this->coordinator->claim(self::SCOPE, self::KEY, self::HASH);

        $this->assertSame(ClaimOutcome::Conflict, $result->outcome);
    }

    /**
     * @return void
     */
    public function testAFreshClaimWithNoResponseIsInFlight(): void
    {
        $this->repository->method('claim')->willReturn(false);
        $this->repository->method('getByKey')->willReturn($this->record(self::HASH, null));
        $this->repository->method('reclaimAbandoned')->willReturn(false);

        $result = $this->coordinator->claim(self::SCOPE, self::KEY, self::HASH);

        $this->assertSame(ClaimOutcome::InFlight, $result->outcome);
    }

    /**
     * A claim whose owner died before storing a response must not wedge the key until the TTL.
     *
     * @return void
     */
    public function testAnAbandonedClaimIsTakenOver(): void
    {
        $this->repository->method('claim')->willReturn(false);
        $this->repository->method('getByKey')->willReturn($this->record(self::HASH, null));
        $this->repository->method('reclaimAbandoned')->willReturn(true);

        $result = $this->coordinator->claim(self::SCOPE, self::KEY, self::HASH);

        $this->assertSame(ClaimOutcome::Claimed, $result->outcome);
    }

    /**
     * Purged between the failed claim and the read: there is nothing to replay and nothing to block.
     *
     * @return void
     */
    public function testAVanishedRowLetsTheCallerProceed(): void
    {
        $this->repository->method('claim')->willReturn(false);
        $this->repository->method('getByKey')->willThrowException(new NoSuchEntityException(__('gone')));

        $result = $this->coordinator->claim(self::SCOPE, self::KEY, self::HASH);

        $this->assertSame(ClaimOutcome::Claimed, $result->outcome);
    }

    /**
     * @param string $hash
     * @param int|null $status
     * @return IdempotencyRecordInterface
     */
    private function record(string $hash, ?int $status): IdempotencyRecordInterface
    {
        $record = $this->createMock(IdempotencyRecordInterface::class);
        $record->method('getRequestHash')->willReturn($hash);
        $record->method('getResponseStatus')->willReturn($status);

        return $record;
    }
}
