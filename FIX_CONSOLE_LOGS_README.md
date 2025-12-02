# Fix Broken console.log Statements

## Problem
You accidentally replaced all `console.log(` with blank, leaving broken statements like:
```javascript
('Loading schedules for bus:', busId);  // ❌ Broken
```

Instead of:
```javascript
console.log('Loading schedules for bus:', busId);  // ✅ Fixed
```

## Solution

### Option 1: Automated Fix (Recommended)

Run this command from your project root:

```bash
find . -type f \( -name "*.js" -o -name "*.blade.php" \) \
  ! -path "*/node_modules/*" \
  ! -path "*/.git/*" \
  ! -path "*/vendor/*" \
  ! -path "*/storage/logs/*" \
  -exec perl -i -pe 's/^(\s+)\((["\x27`])/$1console.log($2/g if /^\s+\(["\x27`]/ && !/function|=>|return|if\s*\(|for\s*\(|while\s*\(/' {} \;
```

Or use the provided script:
```bash
chmod +x SIMPLE_FIX.sh
./SIMPLE_FIX.sh
```

### Option 2: Manual Fix Pattern

Search for:
```
^\s+\(['"]
```

Replace with:
```
console.log(
```

### Option 3: Using VS Code / Editor

1. Open "Find and Replace" (Ctrl/Cmd + Shift + H)
2. Enable regex mode (.*)
3. Find: `^(\s+)\((['"`])`
4. Replace: `$1console.log($2`
5. Review each replacement before accepting

## Files Already Fixed

✅ `core/resources/views/templates/basic/partials/seatlayout.blade.php`
✅ `core/public/agent-sw.js`
✅ `core/resources/views/operator/bookings/create.blade.php` (partially)

## Verification

After fixing, verify with:
```bash
grep -r "^[[:space:]]*(['\"]" --include="*.js" --include="*.blade.php" | grep -v node_modules | grep -v vendor
```

This should return 0 results if all are fixed.

## Testing

1. Test your application
2. Check browser console for errors
3. Review git diff before committing

## Rollback

If something goes wrong:
```bash
git checkout -- .
```

