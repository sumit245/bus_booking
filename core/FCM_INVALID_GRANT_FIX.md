# Fixing "invalid_grant" Error

## Problem

Your notifications are failing with error `"invalid_grant"`. This is a Firebase authentication error, meaning your service account credentials are invalid or expired.

## Quick Fix

### Step 1: Generate New Service Account Key

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: **ghumantoo-dd45d**
3. Go to **Project Settings** (gear icon) > **Service Accounts** tab
4. Click **"Generate New Private Key"**
5. Click **"Generate Key"** to confirm
6. Download the JSON file

### Step 2: Replace Credentials File

1. Replace the file at:
    ```
    /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core/storage/app/firebase-credentials.json
    ```
2. Or update the path in `.env` if you want to use a different location

### Step 3: Clear Cache

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core
php artisan config:clear
php artisan cache:clear
```

### Step 4: Test Again

Send a test notification - it should now work!

## Why This Happens

The "invalid_grant" error typically occurs when:

-   Service account key was deleted or regenerated in Firebase Console
-   Service account key has expired (rare, but possible)
-   System clock is out of sync (Google requires accurate time)
-   Service account permissions were changed

## Verify Service Account Permissions

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select project: **ghumantoo-dd45d**
3. Go to **IAM & Admin** > **IAM**
4. Find your service account: `firebase-adminsdk-fbsvc@ghumantoo-dd45d.iam.gserviceaccount.com`
5. Ensure it has one of these roles:
    - Firebase Admin SDK Administrator Service Agent
    - Firebase Cloud Messaging Admin
    - Editor (full access, not recommended for production)

## Alternative: Check System Time

If regenerating the key doesn't work, check system time:

```bash
# Check current time
date

# Sync time (if needed on Linux/Mac)
sudo sntp -sS time.apple.com  # macOS
# or
sudo ntpdate -s time.nist.gov  # Linux
```

## Still Not Working? - Advanced Troubleshooting

If you've tried regenerating the key and checking permissions, try these:

### 1. Verify Service Account Has FCM Permissions

The service account needs specific permissions. Check in Google Cloud Console:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select project: **ghumantoo-dd45d**
3. Go to **IAM & Admin** > **IAM**
4. Find: `firebase-adminsdk-fbsvc@ghumantoo-dd45d.iam.gserviceaccount.com`
5. Click the edit (pencil) icon
6. Ensure it has at least one of:
    - **Firebase Admin SDK Administrator Service Agent** (recommended)
    - **Firebase Cloud Messaging Admin**
    - Or add custom role with `firebasemessaging.messages.send` permission

### 2. Check if Service Account is Enabled

1. Go to **IAM & Admin** > **Service Accounts**
2. Find your service account
3. Ensure it's **Enabled** (not disabled)

### 3. Regenerate Key from Google Cloud Console

Sometimes regenerating from Google Cloud Console works better:

1. Go to Google Cloud Console > **IAM & Admin** > **Service Accounts**
2. Click on: `firebase-adminsdk-fbsvc@ghumantoo-dd45d.iam.gserviceaccount.com`
3. Go to **Keys** tab
4. Click **Add Key** > **Create New Key** > **JSON**
5. Download and replace the file

### 4. Check System Time Accuracy

The "invalid_grant" error often happens when system time is off:

```bash
# Check current time
date

# Your system time vs PHP time should match
# If they don't match, sync them:
```

**For macOS:**

```bash
sudo sntp -sS time.apple.com
```

**For Linux:**

```bash
sudo ntpdate -s time.nist.gov
```

### 5. Enable Firebase Cloud Messaging API

Ensure the FCM API is enabled:

1. Go to Google Cloud Console > **APIs & Services** > **Enabled APIs**
2. Search for "Firebase Cloud Messaging API"
3. If not enabled, click **Enable**

### 6. Check Laravel Logs for Details

```bash
tail -f storage/logs/laravel.log | grep -A 5 -B 5 "invalid_grant\|FCM notification failed"
```

Look for additional context in the logs - the updated code now logs more details about the error.

### 7. Test with Firebase Console Directly

To verify your credentials work:

1. Go to Firebase Console > **Cloud Messaging**
2. Click **Send test message**
3. Try sending a test notification directly from Firebase
4. If this fails, the issue is with Firebase project configuration, not your code

### 8. Verify Credentials File Encoding

Ensure the file is UTF-8 encoded (no BOM):

```bash
file storage/app/firebase-credentials.json
# Should show: JSON text data
```

### 9. Check PHP Version Compatibility

Ensure you're using a compatible PHP version with the Firebase SDK:

```bash
php -v
# Should be PHP 7.4 or higher
```

### Still Failing?

If all else fails:

1. Create a completely new Firebase project
2. Generate a fresh service account key
3. Test with the new credentials
4. This will help determine if it's a project-level issue
