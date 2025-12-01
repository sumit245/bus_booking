# Laravel Scheduler Setup Guide

## Problem

The scheduled commands (`tickets:expire-pending`, `seat-layout:sync`) are configured but not running automatically because Laravel's task scheduler requires a cron job to trigger it.

## Solution

You need to set up a cron job that runs `php artisan schedule:run` every minute. This command checks which scheduled tasks are due and runs them.

---

## Quick Setup (Automated)

**Run this script for automatic setup:**

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core
./setup-cron.sh
```

The script will:

-   Detect your PHP path automatically
-   Add the cron entry to run scheduler every minute
-   Verify the setup

---

## Manual Setup Instructions

### For macOS (XAMPP)

1. **Open your crontab editor:**

    ```bash
    crontab -e
    ```

2. **Add this line to run the scheduler every minute:**

    ```bash
    * * * * * cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core && /opt/homebrew/bin/php artisan schedule:run >> /dev/null 2>&1
    ```

    **Note:**

    - Replace `/opt/homebrew/bin/php` with your PHP path if different (use `which php` to find it)
    - Replace the path with your actual project path if different
    - The `>> /dev/null 2>&1` part suppresses output (remove it if you want to see logs)

3. **Save and exit** (press `Esc`, then `:wq` if using vim, or `Ctrl+X` then `Y` if using nano)

4. **Verify the cron job is set:**

    ```bash
    crontab -l
    ```

5. **Test the scheduler manually:**
    ```bash
    cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core
    php artisan schedule:run
    ```

### For Production (Linux Server)

1. **SSH into your server**

2. **Edit crontab:**

    ```bash
    crontab -e
    ```

3. **Add this line:**

    ```bash
    * * * * * cd /path/to/your/project/core && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
    ```

    **Replace:**

    - `/path/to/your/project/core` with your actual project path
    - `/usr/bin/php` with your PHP path (use `which php` to find it)

4. **Save and verify:**
    ```bash
    crontab -l
    ```

---

## Alternative: Log Output to File (Recommended for Debugging)

If you want to see what's happening, log the output to a file:

```bash
* * * * * cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core && /opt/homebrew/bin/php artisan schedule:run >> /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core/storage/logs/scheduler.log 2>&1
```

This will log all scheduler activity to `storage/logs/scheduler.log`.

---

## Verify It's Working

1. **Check scheduled tasks:**

    ```bash
    php artisan schedule:list
    ```

2. **Run scheduler manually to test:**

    ```bash
    php artisan schedule:run
    ```

3. **Watch the logs (if logging enabled):**

    ```bash
    tail -f storage/logs/scheduler.log
    ```

4. **Check Laravel logs:**

    ```bash
    tail -f storage/logs/laravel.log
    ```

5. **Create a test pending ticket and wait 15+ minutes:**
    - Create a pending ticket (status 0)
    - Wait 16+ minutes
    - Check if it's expired (status 4)

---

## Current Scheduled Tasks

-   **`seat-layout:sync`** - Runs every minute (syncs seat layouts)
-   **`tickets:expire-pending`** - Runs every 5 minutes (expires pending tickets after 15 minutes)

---

## Troubleshooting

### Cron job not running?

1. **Check if cron service is running:**

    ```bash
    # macOS - cron should be running automatically
    # Linux
    sudo systemctl status cron
    ```

2. **Check cron logs:**

    ```bash
    # macOS
    grep CRON /var/log/system.log

    # Linux
    grep CRON /var/log/syslog
    ```

3. **Check file permissions:**

    - Make sure the PHP file is executable
    - Make sure the project directory is readable

4. **Test PHP path:**

    ```bash
    which php
    /opt/homebrew/bin/php -v
    ```

5. **Test artisan command:**
    ```bash
    cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core
    php artisan schedule:run -v
    ```

### Commands running but tickets not expiring?

1. **Check if tickets are actually pending (status 0):**

    ```bash
    php artisan tinker
    >>> \App\Models\BookedTicket::where('status', 0)->count()
    ```

2. **Check ticket creation time:**

    ```bash
    php artisan tinker
    >>> \App\Models\BookedTicket::where('status', 0)->get(['id', 'created_at'])->each(function($t) { echo "ID: {$t->id}, Created: {$t->created_at}, Age: " . $t->created_at->diffInMinutes(now()) . " minutes\n"; });
    ```

3. **Run expire command manually:**

    ```bash
    php artisan tickets:expire-pending -v
    ```

4. **Check Laravel logs for errors:**
    ```bash
    tail -f storage/logs/laravel.log
    ```

---

## Important Notes

-   **The scheduler runs every minute**, but it only executes commands that are due
-   **`tickets:expire-pending` runs every 5 minutes** - it will expire tickets older than 15 minutes
-   **`seat-layout:sync` runs every minute** - it syncs seat layouts
-   **Commands run in the background** (`runInBackground()`) to prevent blocking
-   **Commands use `withoutOverlapping()`** to prevent multiple instances running simultaneously

---

## For Development (Alternative Approach)

If you don't want to set up cron locally, you can use a process manager like `supervisor` or run the scheduler manually during development:

```bash
# Run scheduler continuously (for development only)
watch -n 60 'cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core && php artisan schedule:run'
```

Or use a Laravel package like `spatie/laravel-cronless-scheduler` for development.
