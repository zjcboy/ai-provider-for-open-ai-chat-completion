#!/usr/bin/env bash

set -e

# 1. Retrieve and increment version number
CURRENT_VERSION=$(grep -E '^\s*\*\s*Version:\s*[0-9]+\.[0-9]+\.[0-9]+' ai-provider-for-openai-chat-completion.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
if [ -z "$CURRENT_VERSION" ]; then
    echo "Error: Could not read current version from main plugin file."
    exit 1
fi

IFS='.' read -r major minor patch <<< "$CURRENT_VERSION"

if [[ "$1" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    NEW_VERSION="$1"
elif [ "$1" == "minor" ]; then
    NEW_VERSION="$major.$((minor + 1)).0"
elif [ "$1" == "major" ]; then
    NEW_VERSION="$((major + 1)).0.0"
else
    NEW_VERSION="$major.$minor.$((patch + 1))"
fi

echo "=== Upgrading version: $CURRENT_VERSION -> $NEW_VERSION ==="

# 2. Update version strings in plugin files
perl -pi -e "s/(Version:\s*)[0-9]+\.[0-9]+\.[0-9]+/\${1}$NEW_VERSION/g" ai-provider-for-openai-chat-completion.php
perl -pi -e "s/(Stable tag:\s*)[0-9]+\.[0-9]+\.[0-9]+/\${1}$NEW_VERSION/g" readme.txt

echo "Version updated in ai-provider-for-openai-chat-completion.php and readme.txt."

# 3. Running Semantic Checks
echo "=== Running Semantic Checks ==="

# PHP Syntax Check (Lint)
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

# PHPStan Static Analysis
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

# 4. Packaging
ZIP_NAME="ai-provider-for-openai-chat-completion-$NEW_VERSION.zip"
TMP_DIR="ai-provider-for-openai-chat-completion"

echo "Packaging plugin to $ZIP_NAME..."

# Remove old zips if any
rm -f ai-provider-for-openai-chat-completion-*.zip

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

# Clean up temp dir
rm -rf "$TMP_DIR"

echo "=== Package Build Successful! ==="
echo "Saved to: $ZIP_NAME"

# 5. Git Commit, Tag & Push
echo "=== Submitting to GitHub ==="

if git diff --quiet ai-provider-for-openai-chat-completion.php readme.txt; then
    echo "No version changes detected in git. Skipping commit/tag."
else
    git add ai-provider-for-openai-chat-completion.php readme.txt
    git commit -m "Bump version to $NEW_VERSION"
    
    echo "Creating Git tag v$NEW_VERSION..."
    # Remove local tag first if exists to prevent conflicts
    git tag -d "v$NEW_VERSION" 2>/dev/null || true
    git tag "v$NEW_VERSION"
    
    echo "Pushing changes and tag to GitHub..."
    if git push origin main && git push origin "v$NEW_VERSION"; then
        echo "Successfully committed, tagged, and pushed to GitHub!"
    else
        echo "Warning: Failed to push to remote. Please check your internet connection or git permissions and push manually:"
        echo "  git push origin main && git push origin v$NEW_VERSION"
    fi
fi
