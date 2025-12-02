#!/usr/bin/env python3
"""
Script to fix broken console.log statements.
Replaces patterns like ('message') with console.log('message')
"""

import re
import os
import sys
from pathlib import Path

# Extensions to process
EXTENSIONS = ['.js', '.blade.php']
EXCLUDE_DIRS = {'node_modules', '.git', 'vendor', 'storage/logs', '__pycache__', '.venv'}

def should_process_file(filepath):
    """Check if file should be processed"""
    path = Path(filepath)
    
    # Check extension
    if path.suffix not in EXTENSIONS and '.blade.php' not in str(path):
        return False
    
    # Check if in excluded directory
    parts = path.parts
    for part in parts:
        if part in EXCLUDE_DIRS:
            return False
    
    return True

def fix_broken_console_log(content):
    """Fix broken console.log statements in content"""
    lines = content.split('\n')
    fixed_lines = []
    changed = False
    
    for line in lines:
        original_line = line
        
        # Pattern 1: Line starts with whitespace + '(' + quote (single, double, or backtick)
        # Matches: ('message') or ("message") or (`message`)
        # Also handles multi-argument: ('message', var) or ("message", var)
        pattern1 = r'^(\s+)\((["\']|`)(.+?)\2([^)]*)\);?\s*$'
        
        # Pattern 2: More complex with variables
        pattern2 = r'^(\s+)\((["\']|`)(.+?)\2\s*,([^)]+)\);?\s*$'
        
        # Check if this looks like a broken console.log
        # It should start with whitespace + opening paren + quote
        if re.match(r'^\s+\(["\'`]', line):
            # Skip if it looks like a valid function call or other construct
            if not any(keyword in line for keyword in ['function', '=>', 'return', 'if', 'else', 'for', 'while']):
                # Fix it by adding console.log
                # Match the indent, opening paren, quote, content, quote, rest, closing paren
                match = re.match(r'^(\s+)\((["\']|`)(.+?)\2([^)]*)\);?\s*$', line)
                if match:
                    indent = match.group(1)
                    quote = match.group(2)
                    message = match.group(3)
                    rest = match.group(4)
                    
                    # Check if rest contains comma (multi-arg console.log)
                    if rest.startswith(','):
                        fixed_line = f"{indent}console.log({quote}{message}{quote}{rest});"
                    else:
                        fixed_line = f"{indent}console.log({quote}{message}{quote}{rest});"
                    
                    line = fixed_line
                    changed = True
        
        fixed_lines.append(line)
    
    return '\n'.join(fixed_lines), changed

def process_file(filepath):
    """Process a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        fixed_content, changed = fix_broken_console_log(content)
        
        if changed:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(fixed_content)
            return True
        return False
    except Exception as e:
        print(f"❌ Error processing {filepath}: {e}", file=sys.stderr)
        return False

def find_files(root_dir):
    """Find all files to process"""
    files = []
    root = Path(root_dir)
    
    for filepath in root.rglob('*'):
        if filepath.is_file() and should_process_file(filepath):
            files.append(filepath)
    
    return files

def main():
    """Main function"""
    print("🔍 Finding broken console.log statements...\n")
    
    root_dir = Path.cwd()
    files = find_files(root_dir)
    
    fixed_count = 0
    for filepath in files:
        if process_file(filepath):
            print(f"✅ Fixed: {filepath}")
            fixed_count += 1
    
    print(f"\n✨ Done! Fixed {fixed_count} file(s).")
    print("⚠️  Please review the changes and test your application.")

if __name__ == '__main__':
    main()

