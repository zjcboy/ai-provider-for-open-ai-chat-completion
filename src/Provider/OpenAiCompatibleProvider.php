<?php

declare(strict_types=1);

namespace WordPress\OpenAiCompatibleAiProvider\Provider;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\OpenAiCompatibleAiProvider\Metadata\OpenAiCompatibleModelMetadataDirectory;
use WordPress\OpenAiCompatibleAiProvider\Models\OpenAiCompatibleTextGenerationModel;

/**
 * Class for the AI Provider for OpenAI Compatible endpoints.
 *
 * @since 1.0.0
 */
class OpenAiCompatibleProvider extends AbstractApiProvider
{
    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function baseUrl(): string
    {
        $url = '';
        if (function_exists('get_option')) {
            $url = get_option('openai_compatible_api_url');
        }
        if (empty($url)) {
            if (defined('OPENAI_COMPATIBLE_API_URL')) {
                $url = OPENAI_COMPATIBLE_API_URL;
            } elseif (getenv('OPENAI_COMPATIBLE_API_URL')) {
                $url = getenv('OPENAI_COMPATIBLE_API_URL');
            } else {
                $url = 'https://api.openai.com/v1';
            }
        }
        return rtrim($url, '/');
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        $capabilities = $modelMetadata->getSupportedCapabilities();
        foreach ($capabilities as $capability) {
            if ($capability->isTextGeneration()) {
                return new OpenAiCompatibleTextGenerationModel($modelMetadata, $providerMetadata);
            }
        }

        throw new RuntimeException(
            'Unsupported model capabilities: ' . implode(', ', $capabilities)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        $credentialsUrl = 'https://platform.openai.com/api-keys';
        $apiUrl = '';
        if (function_exists('get_option')) {
            $apiUrl = get_option('openai_compatible_api_url');
        }
        if (!empty($apiUrl)) {
            $host = parse_url($apiUrl, PHP_URL_HOST);
            if (!empty($host)) {
                $host = strtolower($host);
                if (str_contains($host, 'deepseek.com')) {
                    $credentialsUrl = 'https://platform.deepseek.com/api_keys';
                } elseif (str_contains($host, 'openrouter.ai')) {
                    $credentialsUrl = 'https://openrouter.ai/keys';
                } elseif (str_contains($host, 'openai.com')) {
                    $credentialsUrl = 'https://platform.openai.com/api-keys';
                } elseif (
                    $host === 'localhost' ||
                    $host === '127.0.0.1' ||
                    str_ends_with($host, '.local') ||
                    str_starts_with($host, '192.168.') ||
                    str_starts_with($host, '10.')
                ) {
                    $credentialsUrl = '';
                } else {
                    $scheme = parse_url($apiUrl, PHP_URL_SCHEME) ?: 'https';
                    $credentialsUrl = $scheme . '://' . $host;
                }
            }
        }

        return new ProviderMetadata(
            'openai-compatible',
            'OpenAI Compatible',
            ProviderTypeEnum::cloud(),
            $credentialsUrl,
            RequestAuthenticationMethod::apiKey()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new ListModelsApiBasedProviderAvailability(
            static::modelMetadataDirectory()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new OpenAiCompatibleModelMetadataDirectory();
    }
}
