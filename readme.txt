=== AI Provider for OpenAI Compatible ChatCompletion ===
Contributors: jonathanbossenger
Tags: ai, openai, deepseek, ollama, chatcompletion, artificial-intelligence, connector
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.8
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://jonathanbossenger.com/

A generic OpenAI ChatCompletion compatible AI provider for the WordPress AI Client.

== Description ==

This plugin provides generic OpenAI ChatCompletion-compatible integration for the PHP AI Client SDK. It enables WordPress sites to connect to any OpenAI ChatCompletion-compatible endpoint (including OpenAI, DeepSeek, LocalAI, LM Studio, Ollama, Groq, and more) with customized API URLs, authentication keys, and model lists.

== Features ==

* Text generation with any OpenAI compatible ChatCompletion model
* Configurable API base URL, API authentication key, and custom models
* Dynamic model list query fallback if no custom models are configured
* Automatic provider registration with the AI Client

== Requirements ==

* PHP 7.4 or higher
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional packages are required

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-openai-chat-completion/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the settings (API URL, API Key, and Model List) under Settings -> AI OpenAI Compatible in your WordPress admin panel, OR define them via environment variables/constants.

== Configuration ==

You can configure the plugin via the WordPress Settings page, or by defining constants in your `wp-config.php`:

* `OPENAI_COMPATIBLE_API_URL`: The custom API base URL (e.g. `https://api.openai.com/v1` or `https://api.deepseek.com/v1`).
* `OPENAI_COMPATIBLE_API_KEY`: Your API authentication key.

== Frequently Asked Questions ==

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client package to be installed and active. It provides the implementation that the PHP AI Client registry hooks into.

== Screenshots ==

1. The OpenAI Compatible settings screen where you can customize the API URL, API Key, and list of supported models.

== Changelog ==

= 1.0.0 =

* Initial release
* Support for OpenAI compatible ChatCompletion endpoints
* Dynamic configurations via WordPress options and system constants
