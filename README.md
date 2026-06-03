# AI Provider for OpenAI Compatible ChatCompletion

An AI Provider for OpenAI Compatible ChatCompletion endpoints for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

## Requirements

- PHP 7.4 or higher
- When using with WordPress, requires WordPress 7.0 or higher
    - If using an older WordPress release, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed

## Installation

### As a WordPress Plugin

1. Download the plugin files
2. Upload to `/wp-content/plugins/ai-provider-for-openai-chat-completion/`
3. Ensure the PHP AI Client is installed and active
4. Activate the plugin through the WordPress admin

### As a Composer Package

```bash
composer require wordpress/ai-provider-for-openai-chat-completion
```

## Configuration

### Via WordPress Admin Settings

Go to **Settings -> AI OpenAI Compatible** in your WordPress dashboard to configure:
- **API Base URL**: The custom API endpoint base URL (defaults to `https://api.openai.com/v1`).
- **API Key**: The API token/key.
- **Custom Models**: Comma-separated list of models (e.g. `gpt-4o,gpt-4o-mini,deepseek-chat`). If left empty, the plugin will attempt to query the `/models` endpoint dynamically.

### Via constants / Environment variables

You can define constants in your code or `wp-config.php`:
```php
define('OPENAI_COMPATIBLE_API_URL', 'https://api.deepseek.com/v1');
define('OPENAI_COMPATIBLE_API_KEY', 'your-api-key');
```

## Usage

### With WordPress

The provider automatically registers itself with the PHP AI Client on the `init` hook.

```php
use WordPress\AiClient\AiClient;

// Use the provider
$result = AiClient::prompt('Hello, world!')
    ->usingProvider('openai-compatible')
    ->usingModel('gpt-4o-mini')
    ->generateTextResult();

echo $result->toText();
```

### As a Standalone Package

```php
use WordPress\AiClient\AiClient;
use WordPress\OpenAiCompatibleAiProvider\Provider\OpenAiCompatibleProvider;

// Register the provider
$registry = AiClient::defaultRegistry();
$registry->registerProvider(OpenAiCompatibleProvider::class);

// Configure custom API constants or use putenv
putenv('OPENAI_COMPATIBLE_API_KEY=your-api-key');

// Generate text
$result = AiClient::prompt('Explain quantum computing')
    ->usingProvider('openai-compatible')
    ->usingModel('gpt-4o-mini')
    ->generateTextResult();

echo $result->toText();
```

---

## Publishing to WordPress.org

Follow these steps to submit and publish this plugin to the official WordPress.org Plugin Repository:

### Step 1: Prepare the Submission Package
1. Ensure all development or temporary files (e.g. scratch tests) are excluded.
2. Zip the plugin directory. The folder inside the zip should be named `ai-provider-for-openai-chat-completion` (this matches your WordPress.org slug).
   ```bash
   zip -r ai-provider-for-openai-chat-completion.zip ai-provider-for-openai-chat-completion/ -x "*.git*" "scratch/*"
   ```

### Step 2: Submit for Review
1. Log in to [WordPress.org](https://wordpress.org/) (create an account if you do not have one).
2. Go to the [Add Your Plugin page](https://wordpress.org/plugins/developers/add/).
3. Upload the `.zip` file, fill out the slug (`ai-provider-for-openai-chat-completion`), and submit.
4. The WordPress Plugin Review team will review the code (usually takes 1 to 14 days). They will contact you via email if any adjustments are needed, or with approval.

### Step 3: Publish via SVN
Once approved, WordPress.org will generate a Subversion (SVN) repository for your plugin: `https://plugins.svn.wordpress.org/ai-provider-for-openai-chat-completion/`.

1. Check out the SVN repository locally:
   ```bash
   svn co https://plugins.svn.wordpress.org/ai-provider-for-openai-chat-completion/ local-svn
   ```
2. The folder structure will look like this:
   - `assets/` (for store assets like icons, banners, and screenshots)
   - `branches/` (historical branches)
   - `tags/` (releases/version tags)
   - `trunk/` (the main active code directory)

3. Copy all plugin files into the `trunk/` folder:
   ```bash
   cp -r ai-provider-for-openai-chat-completion/* local-svn/trunk/
   ```

4. Add and commit the changes to `trunk`:
   ```bash
   cd local-svn
   svn add trunk/*
   svn commit -m "Initial release (v1.0.0)" --username <your_wordpress_username>
   ```

5. Tag the release:
   Copy the `trunk` files to `tags/1.0.0/` (matching the `Stable tag` in `readme.txt`):
   ```bash
   svn copy trunk tags/1.0.0
   svn commit -m "Tagging release 1.0.0" --username <your_wordpress_username>
   ```

### Step 4: Add Store Assets (Optional)
To make your plugin look premium on WordPress.org, add assets to the `assets/` folder:
- **Icon**: `icon-128x128.png` and `icon-256x256.png`
- **Banner**: `banner-772x250.png` and `banner-1544x500.png`
- **Screenshot**: `screenshot-1.png` (matching the Screenshot #1 description in `readme.txt`)

Commit the assets folder to SVN:
```bash
svn add assets/*
svn commit -m "Add plugin directory assets"
```

## License

GPL-2.0-or-later
