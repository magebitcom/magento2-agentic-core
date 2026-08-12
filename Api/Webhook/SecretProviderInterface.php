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

interface SecretProviderInterface
{
    /**
     * Resolved at send time so the secret is never stored beside the payload.
     *
     * @param string $scope
     * @return string
     */
    public function getSecret(string $scope): string;
}
