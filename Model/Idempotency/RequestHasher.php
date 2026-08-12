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

class RequestHasher
{
    /**
     * The four fields and their order are fixed: change either and every stored hash becomes a
     * guaranteed conflict on first reuse.
     *
     * @param Http $request
     * @return string
     */
    public function hash(Http $request): string
    {
        return hash('sha256', (string) json_encode([
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'query' => $request->getQuery(),
            'body' => $request->getContent(),
        ]));
    }
}
