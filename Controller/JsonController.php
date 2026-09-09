<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Controller;

use JsonSerializable;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * The plumbing every JSON endpoint needs, whatever protocol it speaks: reading the body, building
 * the response, and standing down the form-key check. What goes in the body, and what an error
 * looks like, is left to whoever extends this.
 */
abstract class JsonController implements ActionInterface, CsrfAwareActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     */
    public function __construct(
        protected readonly JsonFactory $resultJsonFactory,
        protected readonly RequestInterface $request
    ) {
    }

    /**
     * @param array<mixed>|DataObject|JsonSerializable $data
     * @param int $statusCode
     * @return ResultJson
     */
    public function makeJsonResponse(array|DataObject|JsonSerializable $data, int $statusCode = 200): ResultJson
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($data);
        $resultJson->setHttpResponseCode($statusCode);

        return $resultJson;
    }

    /**
     * The request body as an array. Anything that is not a JSON object reads as an empty one, so a
     * caller can report a missing field rather than having to guard the decode itself.
     *
     * @return array<mixed>
     */
    protected function decodedBody(): array
    {
        $decoded = json_decode((string) $this->getHttpRequest()->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Both specifications point at a field with an RFC 9535 JSONPath, which roots at `$` and
     * brackets list positions, while validation reports plain dot-notation paths.
     *
     * @param string $dotted
     * @return string
     */
    public function jsonPath(string $dotted): string
    {
        return '$.' . preg_replace('/\.(\d+)(?=\.|$)/', '[$1]', $dotted);
    }

    /**
     * An agent has no session and no form key, so there is nothing to check and nothing to explain.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return Http
     * @throws LocalizedException If this ran outside a web request
     */
    public function getHttpRequest(): Http
    {
        $request = $this->request;

        if (!$request instanceof Http) {
            throw new LocalizedException(__('Invalid request'));
        }

        return $request;
    }
}
