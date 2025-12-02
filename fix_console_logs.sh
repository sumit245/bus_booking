#!/bin/bash

# Script to fix broken console.log statements
# Replaces patterns like ('message') with console.log('message')

echo "🔍 Finding and fixing broken console.log statements..."

# Find all JavaScript and Blade files
find . -type f \( -name "*.js" -o -name "*.blade.php" \) \
  ! -path "*/node_modules/*" \
  ! -path "*/.git/*" \
  ! -path "*/vendor/*" \
  ! -path "*/storage/logs/*" \
  | while read -r file; do
  
  # Backup original file
  cp "$file" "$file.bak"
  
  # Fix pattern: lines starting with whitespace + '(' + quote
  # Replace with: console.log(
  sed -i '' \
    -e "s/^\([[:space:]]*\)(\(['\"\`]\)/\\1console.log(\\2/g" \
    "$file"
  
  # Check if file was modified
  if ! cmp -s "$file" "$file.bak"; then
    echo "✅ Fixed: $file"
    rm "$file.bak"
  else
    rm "$file.bak"
  fi
done

echo ""
echo "✨ Done! Please review the changes and test your application."
echo "⚠️  If something went wrong, you can restore from git."

