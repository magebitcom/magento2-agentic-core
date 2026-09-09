<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Idempotency;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * The one place a request's idempotency key is read, claimed and answered. Callers get a decision
 * and turn it into their own protocol's response; nothing about wording or status codes is here.
 *
 * Ask for a decision once per request. A second claim on a key this request already owns reads as
 * another caller still working on it.
 */
class Gate
{
    public const HEADER_NAME = 'Idempotency-Key';

    /**
     * Envelope the framework's encryptor puts in front of every value it produces, as
     * `<keyVersion>:<cipherVersion>:<base64>`. A stored response body is JSON, so it always starts
     * with `{` or `[` and can never collide with this.
     */
    private const ENCRYPTED_PREFIX_PATTERN = '/^\d+:\d+:/';

    /**
     * @param Coordinator $coordinator
     * @param RequestHasher $hasher
     * @param EncryptorInterface $encryptor
     * @param string[] $methods Request methods a key applies to
     * @param bool $keyRequired Whether one of those methods without a key is a refusal
     */
    public function __construct(
        private readonly Coordinator $coordinator,
        private readonly RequestHasher $hasher,
        private readonly EncryptorInterface $encryptor,
        private readonly array $methods = ['POST', 'PUT', 'PATCH', 'DELETE'],
        private readonly bool $keyRequired = false
    ) {
    }

    /**
     * @param string $scope Partitions this caller's rows in the shared table
     * @param Http $request
     * @return Decision
     */
    public function decide(string $scope, Http $request): Decision
    {
        if (!$this->appliesTo($request)) {
            return new Decision(DecisionOutcome::Proceed);
        }

        $key = $this->key($request);

        if ($key === null) {
            return new Decision($this->keyRequired ? DecisionOutcome::KeyMissing : DecisionOutcome::Proceed);
        }

        $result = $this->coordinator->claim($scope, $key, $this->hasher->hash($request));

        return match ($result->outcome) {
            ClaimOutcome::Claimed => new Decision(DecisionOutcome::Proceed),
            ClaimOutcome::Conflict => new Decision(DecisionOutcome::Conflict),
            ClaimOutcome::InFlight => new Decision(DecisionOutcome::InFlight),
            ClaimOutcome::Replay => new Decision(
                DecisionOutcome::Replay,
                $this->readBody((string) $result->record?->getResponseBody()),
                (int) $result->record?->getResponseStatus()
            ),
        };
    }

    /**
     * Stores what was sent, so a repeat of the same request gets the same answer.
     *
     * @param string $scope
     * @param Http $request
     * @param string $body
     * @param int $status
     * @return void
     * @throws CouldNotSaveException
     */
    public function remember(string $scope, Http $request, string $body, int $status): void
    {
        $key = $this->key($request);

        if ($key === null || !$this->appliesTo($request)) {
            return;
        }

        $this->coordinator->storeResponse(
            $scope,
            $key,
            $this->hasher->hash($request),
            $status,
            $this->encryptor->encrypt($body)
        );
    }

    /**
     * @param Http $request
     * @return bool
     */
    private function appliesTo(Http $request): bool
    {
        return in_array(strtoupper($request->getMethod()), $this->methods, true);
    }

    /**
     * @param Http $request
     * @return string|null
     */
    private function key(Http $request): ?string
    {
        $key = $request->getHeader(self::HEADER_NAME);
        $key = is_string($key) ? trim($key) : '';

        return $key === '' ? null : $key;
    }

    /**
     * Rows written before stored bodies were encrypted are still inside their TTL on an upgraded
     * install, and the encryptor hands back binary garbage rather than failing when given a value it
     * never encrypted — so the envelope is checked instead of decrypting speculatively. The fallback
     * stops being reachable one TTL window after deploy.
     *
     * @param string $body
     * @return string
     */
    private function readBody(string $body): string
    {
        if ($body === '' || !preg_match(self::ENCRYPTED_PREFIX_PATTERN, $body)) {
            return $body;
        }

        return (string) $this->encryptor->decrypt($body);
    }
}
