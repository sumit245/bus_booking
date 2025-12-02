#!/bin/bash

# Safe script to fix broken console.log statements
# Only replaces lines that match the pattern: whitespace + '(' + quote

echo "🔍 Finding broken console.log statements..."
echo ""

# Count broken statements first
count=$(find . -type f \( -name "*.js" -o -name "*.blade.php" \) \
  ! -path "*/node_modules/*" \
  ! -path "*/.git/*" \
  ! -path "*/vendor/*" \
  ! -path "*/storage/logs/*" \
  -exec grep -c "^[[:space:]]*(['\"]" {} \; 2>/dev/null | awk '{s+=$1} END {print s}')

echo "Found approximately $count broken console.log statements"
echo ""
read -p "Do you want to proceed with fixing them? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Fixing broken console.log statements..."

# Process each file
find . -type f \( -name "*.js" -o -name "*.blade.php" \) \
  ! -path "*/node_modules/*" \
  ! -path "*/.git/*" \
  ! -path "*/vendor/*" \
  ! -path "*/storage/logs/*" \
  | while read -r file; do
  
  # Create backup
  cp "$file" "$file.bak"
  
  # Fix: Replace lines starting with whitespace + '(' + quote with console.log
  # This uses perl for better regex support
  perl -i -pe 's/^(\s+)\((["\x27`])/$1console.log($2/g if /^\s+\(["\x27`]/ && !/function|=>|return|if\s*\(|for\s*\(|while\s*\(/' "$file"
  
  # Check if modified
  if ! cmp -s "$file" "$file.bak"; then
    echo "✅ Fixed: $file"
    rm "$file.bak"
  else
    rm "$file.bak"
  fi
done

echo ""
echo "✨ Done!"
echo "⚠️  Please test your application and review the changes."
echo "💡 If something went wrong, you can restore files from git."

