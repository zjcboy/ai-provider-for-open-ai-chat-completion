# AI Provider for OpenAI Compatible ChatCompletion

An AI Provider for OpenAI Compatible ChatCompletion endpoints for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

## Requirements

- PHP 7.4 or higher
- When using with WordPress, requires WordPress 7.0 or higher
    - If using an older WordPress release, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed

## Installation

### As a WordPress Plugin

1. Download the pre-built `ai-provider-for-openai-chat-completion.zip` file from the [Releases](../../releases) page of the GitHub repository.
2. Upload and install it via **Plugins -> Add New -> Upload Plugin** in your WordPress admin panel, OR upload the unzipped folder to `/wp-content/plugins/`.
3. Ensure the PHP AI Client is installed and active.
4. Activate the plugin through the WordPress admin.

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

## Creating a GitHub Release

You can create a release to provide ZIP downloads using two methods:

### Method A: Automated Release (Recommended)

We have configured a GitHub Actions workflow that automates this when a new Git tag is pushed.

1. **Tag and push a new version**:
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```
2. The workflow will run the semantic checks (PHP Lint & PHPStan) and automatically compile and attach `ai-provider-for-openai-chat-completion.zip` to the release.

### Method B: Manual Release

1. Run the local packaging script to generate the ZIP file:
   ```bash
   ./build.sh
   ```
2. Go to your repository on GitHub.
3. Click on the **Releases** section on the right side of the repository homepage.
4. Click **Draft a new release**.
5. Select or create a tag (e.g. `v1.0.0`), fill in the release title and description.
6. Drag and drop the generated `ai-provider-for-openai-chat-completion.zip` into the **Attach binaries** box.
7. Click **Publish release**.

---

## Publishing to WordPress.org

Follow these steps to submit and publish this plugin to the official WordPress.org Plugin Repository:

### Step 1: Prepare the Submission Package
Exclude any Git files, metadata, or scratch tests, and compress the plugin directory:
```bash
./build.sh
```

### Step 2: Submit for Review
1. Log in to [WordPress.org](https://wordpress.org/).
2. Go to the [Add Your Plugin page](https://wordpress.org/plugins/developers/add/).
3. Upload the `ai-provider-for-openai-chat-completion.zip` file, fill out the slug (`ai-provider-for-openai-chat-completion`), and submit.
4. The WordPress Plugin Review team will review the code and contact you via email once approved.

### Step 3: Publish via SVN
Once approved, WordPress.org will generate a Subversion (SVN) repository for your plugin: `https://plugins.svn.wordpress.org/ai-provider-for-openai-chat-completion/`.

1. Check out the SVN repository locally:
   ```bash
   svn co https://plugins.svn.wordpress.org/ai-provider-for-openai-chat-completion/ local-svn
   ```
2. Copy all plugin files into the `trunk/` folder:
   ```bash
   cp -r ai-provider-for-openai-chat-completion/* local-svn/trunk/
   ```
3. Add and commit the changes to `trunk`:
   ```bash
   cd local-svn
   svn add trunk/*
   svn commit -m "Initial release (v1.0.0)" --username <your_wordpress_username>
   ```
4. Tag the release:
   Copy the `trunk` files to `tags/1.0.0/` and commit:
   ```bash
   svn copy trunk tags/1.0.0
   svn commit -m "Tagging release 1.0.0" --username <your_wordpress_username>
   ```

### Step 4: Add Store Assets (Optional)
Add graphics in the `assets/` folder (such as `banner-772x250.png` and `icon-128x128.png`) and commit:
```bash
svn add assets/*
svn commit -m "Add plugin directory assets"
```

## License

GPL-2.0-or-later
