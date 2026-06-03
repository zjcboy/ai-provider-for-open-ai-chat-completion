#!/usr/bin/env bash

set -e

echo "=== Running Semantic Checks ==="

# 1. PHP Syntax Check (Lint)
echo "Running PHP Lint..."
failed_lint=0
for file in $(find . -type f -name "*.php" -not -path "./vendor/*" -not -path "./scratch/*"); do
    if ! php -l "$file" > /dev/null; then
        echo "Lint failed: $file"
        failed_lint=1
    fi
done

if [ $failed_lint -ne 0 ]; then
    echo "Error: PHP Lint failed. Aborting package build."
    exit 1
fi
echo "PHP Lint passed!"

# 2. PHPStan Static Analysis
if [ -f "./vendor/bin/phpstan" ]; then
    echo "Running PHPStan Static Analysis..."
    if ! ./vendor/bin/phpstan analyse -c phpstan.neon --no-progress --memory-limit 512M; then
        echo "Error: PHPStan analysis failed. Aborting package build."
        exit 1
    fi
    echo "PHPStan analysis passed!"
else
    echo "Warning: PHPStan not found in vendor/bin. Skipping static analysis check."
    echo "Run 'composer install' to enable PHPStan checks."
fi

echo "=== Semantic Checks Passed! ==="

# 3. Packaging
echo "Packaging plugin..."
ZIP_NAME="ai-provider-for-openai-chat-completion.zip"
TMP_DIR="ai-provider-for-openai-chat-completion"

# Remove old zip if exists
rm -f "$ZIP_NAME"

# Create temp directory
rm -rf "$TMP_DIR"
mkdir "$TMP_DIR"

# Copy only production distribution files
cp -r src "$TMP_DIR/"
cp ai-provider-for-openai-chat-completion.php "$TMP_DIR/"
cp readme.txt "$TMP_DIR/"
cp README.md "$TMP_DIR/"
cp LICENSE "$TMP_DIR/"

# Zip
zip -r "$ZIP_NAME" "$TMP_DIR" > /dev/null

# Clean up
rm -rf "$TMP_DIR"

echo "=== Package Build Successful! ==="
echo "Saved to: $ZIP_NAME"
