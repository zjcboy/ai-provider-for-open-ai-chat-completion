<?php
/**
 * Plugin Name: AI Provider for OpenAI Compatible ChatCompletion
 * Plugin URI: https://github.com/zjcboy/ai-provider-for-openai-chat-completion
 * Description: AI Provider for OpenAI Compatible ChatCompletion for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.0
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
 * Registers the OpenAI Compatible AI Provider with the AI Client.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_provider(): void
{
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
        $apiKey = get_option('openai_compatible_api_key');
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
    </div>
    <?php
}

if (function_exists('add_action')) {
    add_action('init', __NAMESPACE__ . '\\register_provider', 5);

    if (function_exists('is_admin') && is_admin()) {
        add_action('admin_menu', __NAMESPACE__ . '\\add_settings_page');
        add_action('admin_init', __NAMESPACE__ . '\\register_settings');
    }
}
