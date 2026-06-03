<?php

declare(strict_types=1);

namespace WordPress\OpenAiCompatibleAiProvider\Models;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use WordPress\OpenAiCompatibleAiProvider\Provider\OpenAiCompatibleProvider;

/**
 * Class for an OpenAI Compatible text generation model.
 *
 * OpenAI Compatible endpoints provide Chat Completions API at /chat/completions,
 * so we use the AbstractOpenAiCompatibleTextGenerationModel base class.
 *
 * @since 1.0.0
 */
class OpenAiCompatibleTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            OpenAiCompatibleProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }
}
