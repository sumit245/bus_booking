#!/bin/bash

# Comprehensive script to fix ALL broken console.log statements
# Replaces patterns like ('message') with console.log('message')

echo "🔍 Finding and fixing ALL broken console.log statements..."
echo ""

# Counter for fixed files
fixed_count=0

# Process all JavaScript and Blade files
find . -type f \( -name "*.js" -o -name "*.blade.php" \) \
  ! -path "*/node_modules/*" \
  ! -path "*/.git/*" \
  ! -path "*/vendor/*" \
  ! -path "*/storage/logs/*" \
  ! -name "vue.js" \
  ! -name "vue.min.js" \
  | while read -r file; do
  
  # Check if file contains broken console.log patterns
  if grep -q "^[[:space:]]*(['\"\`]" "$file" 2>/dev/null; then
    # Backup file
    cp "$file" "$file.bak" 2>/dev/null
    
    # Fix broken console.log statements
    # Pattern: lines starting with whitespace + '(' + quote (single, double, or backtick)
    # Replace with: same whitespace + 'console.log(' + quote
    
    # Use perl for better regex support
    perl -i -pe 's/^(\s+)\((["\x27`])/$1console.log($2/g if /^\s+\(["\x27`]/ && !/function|=>|return|if\s*\(|for\s*\(|while\s*\(/' "$file"
    
    # Check if file was modified
    if ! cmp -s "$file" "$file.bak" 2>/dev/null; then
      echo "✅ Fixed: $file"
      rm "$file.bak" 2>/dev/null
      fixed_count=$((fixed_count + 1))
    else
      rm "$file.bak" 2>/dev/null
    fi
  fi
done

echo ""
echo "✨ Done!"
echo ""
echo "⚠️  Please review the changes and test your application."
echo "💡 If something went wrong, you can restore from git: git checkout -- ."

