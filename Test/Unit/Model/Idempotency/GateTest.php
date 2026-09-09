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
use Magebit\AgenticCore\Model\Idempotency\ClaimOutcome;
use Magebit\AgenticCore\Model\Idempotency\ClaimResult;
use Magebit\AgenticCore\Model\Idempotency\Coordinator;
use Magebit\AgenticCore\Model\Idempotency\DecisionOutcome;
use Magebit\AgenticCore\Model\Idempotency\Gate;
use Magebit\AgenticCore\Model\Idempotency\RequestHasher;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GateTest extends TestCase
{
    private const SCOPE = 'test';
    private const KEY = 'test-key';
    private const BODY = '{"line_items":[]}';

    /**
     * @var Coordinator&MockObject
     */
    private Coordinator $coordinator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->coordinator = $this->createMock(Coordinator::class);
    }

    /**
     * A read carries no side effect to replay, so no key is claimed for one.
     *
     * @return void
     */
    public function testAMethodOutsideTheListIsNeverClaimed(): void
    {
        $this->coordinator->expects($this->never())->method('claim');

        $this->assertSame(
            DecisionOutcome::Proceed,
            $this->gate()->decide(self::SCOPE, $this->request(self::KEY, 'GET'))->outcome
        );
    }

    /**
     * @return void
     */
    public function testAMissingKeyProceedsWhenOneIsNotRequired(): void
    {
        $this->coordinator->expects($this->never())->method('claim');

        $this->assertSame(
            DecisionOutcome::Proceed,
            $this->gate()->decide(self::SCOPE, $this->request(null))->outcome
        );
    }

    /**
     * @return void
     */
    public function testAMissingKeyIsReportedWhenOneIsRequired(): void
    {
        $this->assertSame(
            DecisionOutcome::KeyMissing,
            $this->gate(keyRequired: true)->decide(self::SCOPE, $this->request(null))->outcome
        );
    }

    /**
     * @dataProvider claimProvider
     * @param ClaimOutcome $claim What the coordinator reports
     * @param DecisionOutcome $expected What the caller is told
     * @return void
     */
    public function testEachClaimOutcomeIsReported(ClaimOutcome $claim, DecisionOutcome $expected): void
    {
        $this->coordinator->method('claim')->willReturn(new ClaimResult($claim));

        $this->assertSame(
            $expected,
            $this->gate()->decide(self::SCOPE, $this->request(self::KEY))->outcome
        );
    }

    /**
     * @return array<string, array{ClaimOutcome, DecisionOutcome}>
     */
    public static function claimProvider(): array
    {
        return [
            'claimed' => [ClaimOutcome::Claimed, DecisionOutcome::Proceed],
            'in flight' => [ClaimOutcome::InFlight, DecisionOutcome::InFlight],
            'conflict' => [ClaimOutcome::Conflict, DecisionOutcome::Conflict],
        ];
    }

    /**
     * @return void
     */
    public function testAStoredResponseComesBackDecryptedWithItsStatus(): void
    {
        $this->coordinator->method('claim')->willReturn(
            new ClaimResult(ClaimOutcome::Replay, $this->record('0:3:' . base64_encode(self::BODY), 201))
        );

        $decision = $this->gate()->decide(self::SCOPE, $this->request(self::KEY));

        $this->assertSame(self::BODY, $decision->body);
        $this->assertSame(201, $decision->status);
    }

    /**
     * Rows written before bodies were encrypted are still inside their window on an upgraded
     * install, and the encryptor returns garbage rather than failing on one it never encrypted.
     *
     * @return void
     */
    public function testAPlaintextRowWrittenBeforeEncryptionStillReplays(): void
    {
        $this->coordinator->method('claim')->willReturn(
            new ClaimResult(ClaimOutcome::Replay, $this->record(self::BODY, 200))
        );

        $this->assertSame(
            self::BODY,
            $this->gate()->decide(self::SCOPE, $this->request(self::KEY))->body
        );
    }

    /**
     * @return void
     */
    public function testTheStoredBodyIsEncrypted(): void
    {
        $this->coordinator->expects($this->once())
            ->method('storeResponse')
            ->with(self::SCOPE, self::KEY, $this->isType('string'), 201, '0:3:' . base64_encode(self::BODY));

        $this->gate()->remember(self::SCOPE, $this->request(self::KEY), self::BODY, 201);
    }

    /**
     * @param bool $keyRequired
     * @return Gate
     */
    private function gate(bool $keyRequired = false): Gate
    {
        return new Gate(
            $this->coordinator,
            new RequestHasher(),
            $this->encryptor(),
            ['POST', 'PUT', 'PATCH', 'DELETE'],
            $keyRequired
        );
    }

    /**
     * Stands in for the framework's encryptor, including its `<key>:<cipher>:<payload>` envelope and
     * its habit of returning garbage rather than failing when handed a value it never encrypted.
     *
     * @return EncryptorInterface
     */
    private function encryptor(): EncryptorInterface
    {
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('encrypt')->willReturnCallback(
            static fn (string $value): string => '0:3:' . base64_encode($value)
        );
        $encryptor->method('decrypt')->willReturnCallback(
            static fn (string $value): string => (string) base64_decode(substr($value, 4), true)
        );

        return $encryptor;
    }

    /**
     * @param string $body
     * @param int $status
     * @return IdempotencyRecordInterface
     */
    private function record(string $body, int $status): IdempotencyRecordInterface
    {
        $record = $this->createMock(IdempotencyRecordInterface::class);
        $record->method('getResponseBody')->willReturn($body);
        $record->method('getResponseStatus')->willReturn($status);

        return $record;
    }

    /**
     * @param string|null $key
     * @param string $method
     * @return Http
     */
    private function request(?string $key, string $method = 'POST'): Http
    {
        $request = $this->createMock(Http::class);
        $request->method('getHeader')->willReturn($key ?? false);
        $request->method('getMethod')->willReturn($method);
        $request->method('getPathInfo')->willReturn('/checkout');
        $request->method('getQuery')->willReturn([]);
        $request->method('getContent')->willReturn(self::BODY);

        return $request;
    }
}
