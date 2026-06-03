<?php
/**
 * Plugin Name: AI Provider for OpenAI Compatible ChatCompletion
 * Plugin URI: https://github.com/zjcboy/ai-provider-for-openai-chat-completion
 * Description: AI Provider for OpenAI Compatible ChatCompletion for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.9
 * Author: zjcboy
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-openai-chat-completion
 *
 * @package WordPress\OpenAiCompatibleAiProvider
 */

declare(strict_types=1);

namespace WordPress\OpenAiCompatibleAiProvider;

use WordPress\AiClient\AiClient;
use WordPress\OpenAiCompatibleAiProvider\Provider\OpenAiCompatibleProvider;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;

if (!defined('ABSPATH')) {
    return;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Gets the configured API key, checking the custom option, the core connector option, and constants/env vars.
 *
 * @since 1.0.5
 *
 * @return string API key or empty string.
 */
function get_effective_api_key(): string
{
    if (defined('OPENAI_COMPATIBLE_API_KEY') && !empty(OPENAI_COMPATIBLE_API_KEY)) {
        return OPENAI_COMPATIBLE_API_KEY;
    }
    if (getenv('OPENAI_COMPATIBLE_API_KEY')) {
        return (string) getenv('OPENAI_COMPATIBLE_API_KEY');
    }
    $apiKey = get_option('openai_compatible_api_key', '');
    if (empty($apiKey)) {
        $apiKey = get_option('connectors_ai_openai_compatible_api_key', '');
    }
    return (string) $apiKey;
}

/**
 * Registers the OpenAI Compatible AI Provider with the AI Client.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_provider(): void
{
    // Migrate old option to new option if new option is empty.
    if (function_exists('get_option') && function_exists('update_option')) {
        $new_key = get_option('openai_compatible_api_key', '');
        if (empty($new_key)) {
            $old_key = get_option('connectors_ai_openai_compatible_api_key', '');
            if (!empty($old_key)) {
                update_option('openai_compatible_api_key', $old_key);
                if (function_exists(__NAMESPACE__ . '\\clear_models_transient_cache')) {
                    clear_models_transient_cache();
                }
            }
        }
    }

    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();

    if ($registry->hasProvider(OpenAiCompatibleProvider::class)) {
        return;
    }

    $registry->registerProvider(OpenAiCompatibleProvider::class);

    // Set request authentication from options if present
    if (function_exists('get_option')) {
        $apiKey = get_effective_api_key();
        if (!empty($apiKey)) {
            $registry->setProviderRequestAuthentication(
                OpenAiCompatibleProvider::class,
                new ApiKeyRequestAuthentication($apiKey)
            );
        }
    }
}

/**
 * Adds the settings page under Settings.
 *
 * @since 1.0.0
 *
 * @return void
 */
function add_settings_page(): void
{
    add_options_page(
        __('OpenAI Compatible AI Settings', 'ai-provider-for-openai-chat-completion'),
        __('AI OpenAI Compatible', 'ai-provider-for-openai-chat-completion'),
        'manage_options',
        'openai-compatible-settings',
        __NAMESPACE__ . '\\render_settings_page'
    );
}

/**
 * Registers options settings.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_settings(): void
{
    register_setting('openai_compatible_settings_group', 'openai_compatible_api_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => 'https://api.openai.com/v1',
    ]);
    register_setting('openai_compatible_settings_group', 'openai_compatible_api_key', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('openai_compatible_settings_group', 'openai_compatible_models', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'gpt-4o,gpt-4o-mini',
    ]);
}

/**
 * Renders the settings page HTML.
 *
 * @since 1.0.0
 *
 * @return void
 */
function render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['refresh_models']) && check_admin_referer('refresh_openai_compatible_models')) {
        fetch_raw_models_data(true);
        wp_safe_redirect(admin_url('options-general.php?page=openai-compatible-settings'));
        exit;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('OpenAI Compatible AI Settings', 'ai-provider-for-openai-chat-completion'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('openai_compatible_settings_group');
            do_settings_sections('openai-compatible-settings');
            ?>
            <table class="form-table" role="presentation">
                <tr valign="top">
                    <th scope="row"><label
                            for="openai_compatible_api_url"><?php esc_html_e('API Base URL', 'ai-provider-for-openai-chat-completion'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="openai_compatible_api_url" name="openai_compatible_api_url"
                            value="<?php echo esc_attr(get_option('openai_compatible_api_url', 'https://api.openai.com/v1')); ?>"
                            class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('The base URL of the OpenAI-compatible API (e.g. https://api.openai.com/v1 or https://api.deepseek.com/v1).', 'ai-provider-for-openai-chat-completion'); ?>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label
                            for="openai_compatible_api_key"><?php esc_html_e('API Key', 'ai-provider-for-openai-chat-completion'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="openai_compatible_api_key" name="openai_compatible_api_key"
                            value="<?php echo esc_attr(get_option('openai_compatible_api_key')); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('Your API authentication key.', 'ai-provider-for-openai-chat-completion'); ?>
                            <?php if (defined('OPENAI_COMPATIBLE_API_KEY')): ?>
                                <br />
                                <span
                                    style="color: green; font-weight: bold;"><?php esc_html_e('(Already defined via constant in wp-config.php)', 'ai-provider-for-openai-chat-completion'); ?></span>
                            <?php elseif (get_option('connectors_ai_openai_compatible_api_key')): ?>
                                <br />
                                <span
                                    style="color: green; font-weight: bold;"><?php esc_html_e('(Already configured in Jetpack/WordPress Connectors settings)', 'ai-provider-for-openai-chat-completion'); ?></span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label
                            for="openai_compatible_models"><?php esc_html_e('Custom Models', 'ai-provider-for-openai-chat-completion'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="openai_compatible_models" name="openai_compatible_models"
                            value="<?php echo esc_attr(get_option('openai_compatible_models', 'gpt-4o,gpt-4o-mini')); ?>"
                            class="large-text" />
                        <p class="description">
                            <?php esc_html_e('Comma-separated list of model IDs to make available (e.g., gpt-4o,gpt-4o-mini,deepseek-chat). If empty, the plugin will try to fetch models dynamically from the /models endpoint.', 'ai-provider-for-openai-chat-completion'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <?php
        $apiKey = get_effective_api_key();
        if (!empty($apiKey)):
            $models = fetch_raw_models_data();
            ?>
            <hr style="margin: 30px 0 20px;" />
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                <h2 style="margin: 0;"><?php esc_html_e('Available Models', 'ai-provider-for-openai-chat-completion'); ?></h2>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=openai-compatible-settings&refresh_models=1'), 'refresh_openai_compatible_models')); ?>" class="button button-secondary">
                    <?php esc_html_e('Refresh Models List', 'ai-provider-for-openai-chat-completion'); ?>
                </a>
            </div>
            <?php if (empty($models)): ?>
                <div class="notice notice-warning inline" style="margin: 0 0 15px;"><p><?php esc_html_e('No models found, or unable to fetch models list from the configured endpoint.', 'ai-provider-for-openai-chat-completion'); ?></p></div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 30%; font-weight: bold;"><?php esc_html_e('Model Name / ID', 'ai-provider-for-openai-chat-completion'); ?></th>
                            <th scope="col" style="width: 15%; font-weight: bold;"><?php esc_html_e('Context Length', 'ai-provider-for-openai-chat-completion'); ?></th>
                            <th scope="col" style="width: 25%; font-weight: bold;"><?php esc_html_e('Pricing (per 1M tokens)', 'ai-provider-for-openai-chat-completion'); ?></th>
                            <th scope="col" style="width: 30%; font-weight: bold;"><?php esc_html_e('Description', 'ai-provider-for-openai-chat-completion'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($models as $model):
                            $modelId = $model['id'] ?? '';
                            $modelName = $model['name'] ?? $modelId;
                            $contextLength = isset($model['context_length']) ? number_format((int)$model['context_length']) . ' tokens' : __('N/A', 'ai-provider-for-openai-chat-completion');
                            $description = $model['description'] ?? __('No description provided.', 'ai-provider-for-openai-chat-completion');
                            $pricing = format_model_pricing($model);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($modelName); ?></strong>
                                    <br /><code style="font-size: 11px;"><?php echo esc_html($modelId); ?></code>
                                </td>
                                <td><?php echo esc_html($contextLength); ?></td>
                                <td><code><?php echo esc_html($pricing); ?></code></td>
                                <td><?php echo esc_html($description); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif;
        else:
            ?>
            <hr style="margin: 30px 0 20px;" />
            <h2><?php esc_html_e('Available Models', 'ai-provider-for-openai-chat-completion'); ?></h2>
            <p class="description"><?php esc_html_e('Please configure and save your API Key to fetch and display the list of available models.', 'ai-provider-for-openai-chat-completion'); ?></p>
        <?php
        endif;
        ?>
    </div>
    <?php
}

/**
 * Fetches raw models data from the configured OpenAI-compatible API.
 * Caches the response in a transient.
 *
 * @since 1.0.6
 *
 * @param bool $force_refresh Whether to force refresh and bypass cache.
 * @return array|null List of models or null on failure.
 */
function fetch_raw_models_data(bool $force_refresh = false): ?array
{
    if (!function_exists('get_option')) {
        return null;
    }

    $apiUrl = get_option('openai_compatible_api_url', 'https://api.openai.com/v1');
    $apiKey = get_effective_api_key();
    if (empty($apiKey)) {
        return null;
    }

    $cacheKey = 'oa_compat_raw_models_' . md5($apiUrl . $apiKey);

    if (!$force_refresh) {
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $url = rtrim($apiUrl, '/') . '/models';
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 15,
        'sslverify' => false,
        'reject_unsafe_urls' => false,
    ];

    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
        return null;
    }

    $models = $data['data'];
    set_transient($cacheKey, $models, 86400); // Cache for 24 hours
    return $models;
}

/**
 * Formats model pricing details for display.
 * Supports OpenRouter compatible pricing schemas.
 *
 * @since 1.0.6
 *
 * @param array $model Model data.
 * @return string Formatted pricing string.
 */
function format_model_pricing(array $model): string
{
    if (isset($model['pricing']) && is_array($model['pricing'])) {
        $prompt = isset($model['pricing']['prompt']) ? floatval($model['pricing']['prompt']) : null;
        $completion = isset($model['pricing']['completion']) ? floatval($model['pricing']['completion']) : null;

        if (null !== $prompt && null !== $completion) {
            if ($prompt == 0 && $completion == 0) {
                return __('Free', 'ai-provider-for-openai-chat-completion');
            }

            $promptPerMil = $prompt * 1000000;
            $completionPerMil = $completion * 1000000;

            return sprintf(
                __('In: $%s / Out: $%s', 'ai-provider-for-openai-chat-completion'),
                number_format($promptPerMil, 2),
                number_format($completionPerMil, 2)
            );
        }
    }
    return __('N/A', 'ai-provider-for-openai-chat-completion');
}

/**
 * Clears the transient cache when the settings options are updated.
 *
 * @since 1.0.0
 *
 * @return void
 */
function clear_models_transient_cache(): void
{
    if (function_exists('delete_transient')) {
        $apiUrl = get_option('openai_compatible_api_url', 'https://api.openai.com/v1');
        $apiKey = get_effective_api_key();
        $transientKey = 'oa_compat_models_' . md5($apiUrl . $apiKey);
        delete_transient($transientKey);

        $rawTransientKey = 'oa_compat_raw_models_' . md5($apiUrl . $apiKey);
        delete_transient($rawTransientKey);

        // Also invalidate PSR-16 cache on the registry's directory if it is loaded
        if (class_exists(AiClient::class)) {
            try {
                $registry = AiClient::defaultRegistry();
                if ($registry->hasProvider(OpenAiCompatibleProvider::class)) {
                    $providerClass = $registry->getProviderClassName('openai-compatible');
                    /** @var mixed $directory */
                    $directory = $providerClass::modelMetadataDirectory();
                    if (method_exists($directory, 'invalidateCaches')) {
                        $directory->invalidateCaches();
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }
    }
}

/**
 * Filters the HTTP request arguments to disable SSL verification and local IP checks
 * specifically for requests to the configured OpenAI-compatible API.
 *
 * @since 1.0.0
 *
 * @param array<string, mixed> $args HTTP request arguments.
 * @param string               $url  The request URL.
 * @return array<string, mixed> Filtered arguments.
 */
function filter_http_request_args(array $args, string $url): array
{
    if (function_exists('get_option')) {
        $customUrl = get_option('openai_compatible_api_url');
        if (!empty($customUrl)) {
            $customHost = parse_url($customUrl, PHP_URL_HOST);
            $requestHost = parse_url($url, PHP_URL_HOST);
            if (!empty($customHost) && $customHost === $requestHost) {
                $args['sslverify'] = false;
                $args['reject_unsafe_urls'] = false;
            }
        }
    }
    return $args;
}

/**
 * Overrides the setting name for the OpenAI Compatible connector to match the custom setting page.
 * This ensures that both the Connectors settings page and the custom settings page update the same option,
 * and that the AI Client properly recognizes that the connector is configured.
 *
 * @since 1.0.7
 *
 * @param mixed $registry The WP_Connector_Registry instance.
 * @return void
 */
function override_connector_setting_name($registry): void
{
    if (!class_exists('\\WP_Connector_Registry') || !($registry instanceof \WP_Connector_Registry)) {
        return;
    }
    if ($registry->is_registered('openai-compatible')) {
        $connector = $registry->unregister('openai-compatible');
        $connector['authentication']['setting_name'] = 'openai_compatible_api_key';
        $registry->register('openai-compatible', $connector);
    }
}

if (function_exists('add_filter')) {
    add_filter('http_request_args', __NAMESPACE__ . '\\filter_http_request_args', 10, 2);
}

if (function_exists('add_action')) {
    add_action('wp_connectors_init', __NAMESPACE__ . '\\override_connector_setting_name');
    add_action('init', __NAMESPACE__ . '\\register_provider', 5);

    if (function_exists('is_admin') && is_admin()) {
        add_action('admin_menu', __NAMESPACE__ . '\\add_settings_page');
        add_action('admin_init', __NAMESPACE__ . '\\register_settings');
        add_action('update_option_openai_compatible_api_url', __NAMESPACE__ . '\\clear_models_transient_cache');
        add_action('update_option_openai_compatible_api_key', __NAMESPACE__ . '\\clear_models_transient_cache');
        add_action('update_option_connectors_ai_openai_compatible_api_key', __NAMESPACE__ . '\\clear_models_transient_cache');
        add_action('update_option_openai_compatible_models', __NAMESPACE__ . '\\clear_models_transient_cache');
    }
}

