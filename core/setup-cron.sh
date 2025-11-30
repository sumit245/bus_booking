#!/bin/bash
# Laravel Scheduler Cron Setup Script

PROJECT_PATH="/Applications/XAMPP/xamppfiles/htdocs/bus_booking/core"
PHP_PATH=$(which php)

echo "🔧 Laravel Scheduler Cron Setup"
echo "================================"
echo ""
echo "Project Path: $PROJECT_PATH"
echo "PHP Path: $PHP_PATH"
echo ""

# Check if PHP path exists
if [ ! -f "$PHP_PATH" ]; then
    echo "❌ Error: PHP not found at $PHP_PATH"
    echo "Please install PHP or update the PHP_PATH variable"
    exit 1
fi

# Check if project path exists
if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ Error: Project path not found: $PROJECT_PATH"
    echo "Please update PROJECT_PATH in this script"
    exit 1
fi

# Create cron entry
CRON_ENTRY="* * * * * cd $PROJECT_PATH && $PHP_PATH artisan schedule:run >> /dev/null 2>&1"

echo "📋 Cron entry to be added:"
echo "$CRON_ENTRY"
echo ""
echo "This will run the Laravel scheduler every minute."
echo ""
read -p "Do you want to add this to your crontab? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Check if cron entry already exists
    if crontab -l 2>/dev/null | grep -q "schedule:run"; then
        echo "⚠️  A schedule:run entry already exists in your crontab."
        echo ""
        echo "Current crontab entries:"
        crontab -l | grep "schedule:run"
        echo ""
        read -p "Do you want to replace it? (y/n) " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            # Remove old entries
            crontab -l 2>/dev/null | grep -v "schedule:run" | crontab -
            # Add new entry
            (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
            echo "✅ Cron entry updated successfully!"
        else
            echo "❌ Setup cancelled."
            exit 0
        fi
    else
        # Add new entry
        (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
        echo "✅ Cron entry added successfully!"
    fi
    
    echo ""
    echo "📋 Your current crontab:"
    crontab -l
    echo ""
    echo "✅ Setup complete! The scheduler will run every minute."
    echo ""
    echo "To test it immediately, run:"
    echo "  cd $PROJECT_PATH && $PHP_PATH artisan schedule:run"
    echo ""
    echo "To view scheduled tasks:"
    echo "  cd $PROJECT_PATH && $PHP_PATH artisan schedule:list"
else
    echo "❌ Setup cancelled."
    exit 0
fi
