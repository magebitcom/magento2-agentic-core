<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Api;

use Magebit\AgenticCore\Api\Data\SigningKeyInterface;

interface SigningKeyRepositoryInterface
{
    /**
     * The key new messages are signed with, generated on first use so a merchant never has to create
     * one by hand before anything can be sent.
     *
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface
     */
    public function getSigningKey(string $scope, int $storeId): SigningKeyInterface;

    /**
     * Every key a verifier may still encounter, newest first. Retired keys stay here through their
     * grace window so signatures already in flight keep verifying.
     *
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface[]
     */
    public function getPublishableKeys(string $scope, int $storeId): array;

    /**
     * Adds a new key and stops signing with the previous one, leaving it publishable.
     *
     * @param string $scope
     * @param int $storeId
     * @return SigningKeyInterface The new signing key
     */
    public function rotate(string $scope, int $storeId): SigningKeyInterface;

    /**
     * Removes retired keys past the grace window. A key still being signed with is never removed.
     *
     * @param string $scope
     * @param int $storeId
     * @param int $graceDays
     * @return int Number of keys removed
     */
    public function purgeRetired(string $scope, int $storeId, int $graceDays): int;
}
