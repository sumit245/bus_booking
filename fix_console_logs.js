#!/usr/bin/env node

/**
 * Script to fix broken console.log statements
 * Replaces patterns like ('message') with console.log('message')
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Files to process (JavaScript and Blade files)
const extensions = ['.js', '.blade.php'];
const excludeDirs = ['node_modules', '.git', 'vendor', 'storage/logs'];

function shouldProcessFile(filePath) {
    const ext = path.extname(filePath);
    if (!extensions.includes(ext) && !filePath.includes('.blade.php')) {
        return false;
    }
    
    // Exclude certain directories
    for (const excludeDir of excludeDirs) {
        if (filePath.includes(excludeDir)) {
            return false;
        }
    }
    
    return true;
}

function fixConsoleLogs(content) {
    // Pattern 1: Lines starting with whitespace + opening paren + string literal
    // Matches: ('message') or ('message', variable) or ("message")
    let fixed = content.replace(/^(\s+)\((['"`])(.+?)(['"`])([^)]*)\);?(\s*)$/gm, (match, indent, quote1, message, quote2, rest, end) => {
        // Skip if it looks like a function call or other valid syntax
        if (match.includes('function') || match.includes('=>') || match.includes('return')) {
            return match;
        }
        return `${indent}console.log(${quote1}${message}${quote2}${rest});${end}`;
    });
    
    // Pattern 2: Lines with template literals: (`message`)
    fixed = fixed.replace(/^(\s+)\((`)(.+?)(`)([^)]*)\);?(\s*)$/gm, (match, indent, quote1, message, quote2, rest, end) => {
        if (match.includes('function') || match.includes('=>') || match.includes('return')) {
            return match;
        }
        return `${indent}console.log(${quote1}${message}${quote2}${rest});${end}`;
    });
    
    // Pattern 3: More complex patterns with variables
    // Matches: ('message:', variable) or ('message', var1, var2)
    fixed = fixed.replace(/^(\s+)\((['"`])(.+?)(['"`])\s*,([^)]+)\);?(\s*)$/gm, (match, indent, quote1, message, quote2, variables, end) => {
        if (match.includes('function') || match.includes('=>') || match.includes('return')) {
            return match;
        }
        return `${indent}console.log(${quote1}${message}${quote2},${variables});${end}`;
    });
    
    return fixed;
}

function processFile(filePath) {
    try {
        const content = fs.readFileSync(filePath, 'utf8');
        const originalContent = content;
        const fixedContent = fixConsoleLogs(content);
        
        if (originalContent !== fixedContent) {
            fs.writeFileSync(filePath, fixedContent, 'utf8');
            console.log(`✅ Fixed: ${filePath}`);
            return true;
        }
        return false;
    } catch (error) {
        console.error(`❌ Error processing ${filePath}:`, error.message);
        return false;
    }
}

function findFiles(dir, fileList = []) {
    const files = fs.readdirSync(dir);
    
    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);
        
        if (stat.isDirectory()) {
            if (!excludeDirs.some(dir => filePath.includes(dir))) {
                findFiles(filePath, fileList);
            }
        } else if (shouldProcessFile(filePath)) {
            fileList.push(filePath);
        }
    });
    
    return fileList;
}

// Main execution
console.log('🔍 Finding broken console.log statements...\n');

const rootDir = process.cwd();
const files = findFiles(rootDir);
let fixedCount = 0;

files.forEach(file => {
    if (processFile(file)) {
        fixedCount++;
    }
});

console.log(`\n✨ Done! Fixed ${fixedCount} file(s).`);
console.log('\n⚠️  Please review the changes and test your application.');

