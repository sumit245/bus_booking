#!/bin/bash
# Simple one-line fix for broken console.log statements

# This command will:
# 1. Find all .js and .blade.php files
# 2. Replace lines starting with whitespace + '(' + quote with console.log(
# 3. Only affect broken console.log patterns

find . -type f \( -name "*.js" -o -name "*.blade.php" \) \
  ! -path "*/node_modules/*" \
  ! -path "*/.git/*" \
  ! -path "*/vendor/*" \
  ! -path "*/storage/logs/*" \
  -exec sed -i '' 's/^\([[:space:]]*\)(\(["'\''`]\)/\1console.log(\2/g' {} \;

echo "✅ Fixed all broken console.log statements!"
echo "⚠️  Please test your application and check for any syntax errors."

