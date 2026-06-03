<?php

declare(strict_types=1);

namespace WordPress\OpenAiCompatibleAiProvider\Metadata;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\OpenAiCompatibleAiProvider\Provider\OpenAiCompatibleProvider;

/**
 * Class for the OpenAI Compatible model metadata directory.
 *
 * Discovers models available from the configured provider.
 *
 * @since 1.0.0
 *
 * @phpstan-type OpenAiCompatibleModelData array{
 *     id: string,
 *     name?: string,
 *     architecture?: array{
 *         modality?: string
 *     }
 * }
 * @phpstan-type OpenAiCompatibleModelsResponseData array{
 *     data: list<OpenAiCompatibleModelData>
 * }
 */
class OpenAiCompatibleModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory
{
    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected function sendListModelsRequest(): array
    {
        // 1. Try to load custom models configured in the WordPress settings page.
        if (function_exists('get_option')) {
            $customModelsOpt = get_option('openai_compatible_models');
            if (!empty($customModelsOpt)) {
                $customModels = array_filter(array_map('trim', explode(',', $customModelsOpt)));
                if (!empty($customModels)) {
                    $modelMetadataMap = [];
                    foreach ($customModels as $modelId) {
                        $capabilities = $this->determineCapabilities(['id' => $modelId]);
                        $options = $this->determineSupportedOptions(['id' => $modelId]);

                        $modelMetadataMap[$modelId] = new ModelMetadata(
                            $modelId,
                            $modelId,
                            $capabilities,
                            $options
                        );
                    }
                    return $modelMetadataMap;
                }
            }
        }

        // 2. Otherwise, fetch from the /models API.
        try {
            $httpTransporter = $this->getHttpTransporter();

            $request = new Request(
                HttpMethodEnum::GET(),
                OpenAiCompatibleProvider::url($this->getModelsApiPath()),
                [],
                null
            );

            $request = $this->getRequestAuthentication()->authenticateRequest($request);

            $response = $httpTransporter->send($request);

            $modelsMetadata = $this->parseResponseToModelMetadataList($response);

            $modelMetadataMap = [];
            foreach ($modelsMetadata as $modelMetadata) {
                $modelMetadataMap[$modelMetadata->getId()] = $modelMetadata;
            }

            return $modelMetadataMap;
        } catch (\Exception $e) {
            // 3. Fallback to default models if request fails (e.g. offline, local host or missing credentials)
            $defaultModels = ['gpt-4o', 'gpt-4o-mini'];
            $modelMetadataMap = [];
            foreach ($defaultModels as $modelId) {
                $capabilities = $this->determineCapabilities(['id' => $modelId]);
                $options = $this->determineSupportedOptions(['id' => $modelId]);

                $modelMetadataMap[$modelId] = new ModelMetadata(
                    $modelId,
                    $modelId,
                    $capabilities,
                    $options
                );
            }
            return $modelMetadataMap;
        }
    }

    /**
     * Parses the OpenAI Compatible API response to a list of model metadata.
     *
     * @since 1.0.0
     *
     * @param Response $response HTTP response.
     * @return ModelMetadata[] List of model metadata.
     * @throws ResponseException If response is invalid.
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        /** @var OpenAiCompatibleModelsResponseData $responseData */
        $responseData = $response->getData();

        if (!isset($responseData['data']) || !is_array($responseData['data'])) {
            throw ResponseException::fromMissingData('OpenAI Compatible', 'data');
        }

        $modelsMetadata = [];
        foreach ($responseData['data'] as $model) {
            $modelMetadata = $this->parseModelToMetadata($model);
            if (null !== $modelMetadata) {
                $modelsMetadata[] = $modelMetadata;
            }
        }

        return $modelsMetadata;
    }

    /**
     * Parses a single model from the API response to ModelMetadata.
     *
     * @since 1.0.0
     *
     * @param array $model Model data from API.
     * @return ModelMetadata|null Model metadata or null if model should be skipped.
     */
    protected function parseModelToMetadata(array $model): ?ModelMetadata
    {
        if (!isset($model['id']) || empty($model['id'])) {
            return null;
        }

        $modelId = $model['id'];
        $modelName = $model['name'] ?? $modelId;

        $capabilities = $this->determineCapabilities($model);
        $options = $this->determineSupportedOptions($model);

        return new ModelMetadata(
            $modelId,
            $modelName,
            $capabilities,
            $options
        );
    }

    /**
     * Determines model capabilities based on model data.
     *
     * @since 1.0.0
     *
     * @param array $model Model data.
     * @return CapabilityEnum[] List of capabilities.
     */
    protected function determineCapabilities(array $model): array
    {
        $capabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];

        $modelId = strtolower($model['id'] ?? '');

        // Parse optional architecture modality if present
        $modality = $model['architecture']['modality'] ?? '';
        if (!empty($modality)) {
            if (str_contains($modality, 'image')) {
                $capabilities[] = CapabilityEnum::imageGeneration();
            }
        } else {
            // Fallback check based on model ID
            if (str_contains($modelId, 'dall-e') || str_contains($modelId, 'image-generator')) {
                $capabilities[] = CapabilityEnum::imageGeneration();
            }
        }

        return $capabilities;
    }

    /**
     * Determines supported options based on model data.
     *
     * @since 1.0.0
     *
     * @param array $model Model data.
     * @return SupportedOption[] List of supported options.
     */
    protected function determineSupportedOptions(array $model): array
    {
        $options = [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::stopSequences()),
            new SupportedOption(OptionEnum::customOptions()),
        ];

        $modelId = strtolower($model['id'] ?? '');
        $modality = $model['architecture']['modality'] ?? '';

        $inputModalities = [ModalityEnum::text()];
        $outputModalities = [ModalityEnum::text()];

        if (!empty($modality)) {
            if (str_contains($modality, '+image->') || str_contains($modality, 'image+')) {
                $inputModalities[] = ModalityEnum::image();
            }
            if (str_contains($modality, '->text+image') || str_contains($modality, '->image')) {
                $outputModalities[] = ModalityEnum::image();
            }
        } else {
            // Guess vision capability based on model ID
            if (str_contains($modelId, 'vision') || str_contains($modelId, 'gpt-4o') || str_contains($modelId, 'claude-3') || str_contains($modelId, 'gemini-1.5')) {
                $inputModalities[] = ModalityEnum::image();
            }
        }

        $options[] = new SupportedOption(OptionEnum::inputModalities(), [$inputModalities]);
        $options[] = new SupportedOption(OptionEnum::outputModalities(), [$outputModalities]);

        return $options;
    }

    /**
     * Gets the API path for listing models.
     *
     * @since 1.0.0
     *
     * @return string API path.
     */
    protected function getModelsApiPath(): string
    {
        return '/models';
    }
}
