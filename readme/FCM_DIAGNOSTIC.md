# FCM "invalid_grant" Error - Comprehensive Diagnosis

## Current Status

Based on your logs:

-   ✅ Firebase initializes successfully
-   ✅ Credentials file is valid JSON
-   ✅ Responses are extracted correctly
-   ❌ All notifications fail with `"invalid_grant"` error

This means the error occurs during the **actual sending**, not during setup.

## Fixing ConnectException (Network Issue)

### Step 1: Test Network Connectivity

```bash
# Test if you can reach Google/Firebase servers
curl -I https://www.googleapis.com
curl -I https://fcm.googleapis.com

# Test DNS resolution
nslookup fcm.googleapis.com
```

### Step 2: Check Firewall/Proxy

If you're behind a firewall or proxy:

1. Ensure `*.googleapis.com` is whitelisted
2. Check proxy settings in PHP (if configured)
3. Test from command line vs. web server

### Step 3: Check XAMPP Network Settings

XAMPP might have network restrictions:

1. Check XAMPP firewall settings
2. Ensure PHP can make outbound HTTPS connections
3. Test: `php -r "file_get_contents('https://www.google.com');"`

### Step 4: Check PHP cURL/OpenSSL

Ensure PHP has network extensions enabled:

```bash
php -m | grep -i "curl\|openssl"
# Should show: curl, openssl
```

If missing, enable in `php.ini`:

```ini
extension=curl
extension=openssl
```

---

## Fixing "invalid_grant" Error

## Most Likely Causes (in order of probability)

### 1. Firebase Cloud Messaging API Not Enabled ⚠️ MOST COMMON

The FCM API must be enabled in your Google Cloud project.

**Check:**

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select project: **ghumantoo-dd45d**
3. Navigate to: **APIs & Services** > **Enabled APIs & services**
4. Search for: **"Firebase Cloud Messaging API"** or **"FCM API"**

**If NOT enabled:**

1. Click **"+ ENABLE APIS AND SERVICES"**
2. Search for "Firebase Cloud Messaging API"
3. Click on it and click **ENABLE**
4. Wait 1-2 minutes for it to activate
5. Try sending notifications again

### 2. Service Account Lacks FCM Permissions

**Check Permissions:**

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select project: **ghumantoo-dd45d**
3. Navigate to: **IAM & Admin** > **IAM**
4. Find: `firebase-adminsdk-fbsvc@ghumantoo-dd45d.iam.gserviceaccount.com`

**Required Role:**

-   Must have: **Firebase Admin SDK Administrator Service Agent**
-   OR: **Editor** role (full access)
-   OR: Custom role with `firebasemessaging.messages.send` permission

**To Fix:**

1. Click the edit (pencil) icon next to the service account
2. Add role: **Firebase Admin SDK Administrator Service Agent**
3. Save and wait 1-2 minutes
4. Try again

### 3. Service Account Key Needs Regeneration

Even if the file looks valid, the key might be:

-   Expired
-   Revoked in Firebase Console
-   Generated with wrong permissions

**Regenerate:**

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select project: **ghumantoo-dd45d**
3. Go to: **Project Settings** > **Service Accounts**
4. Click **"Generate New Private Key"**
5. **Important:** Delete the old key first (in Google Cloud Console > Service Accounts > Keys)
6. Download new JSON
7. Replace file at: `storage/app/firebase-credentials.json`
8. Clear cache: `php artisan config:clear && php artisan cache:clear`

### 4. System Time Synchronization

**Check:**

```bash
date
```

**If time is wrong:**

-   **macOS:** `sudo sntp -sS time.apple.com`
-   **Linux:** `sudo ntpdate -s time.nist.gov`

Google OAuth requires time to be accurate within 5 minutes.

### 5. Firebase Project Configuration

**Check in Firebase Console:**

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select project: **ghumantoo-dd45d**
3. Go to: **Project Settings** > **General**
4. Verify:
    - Project ID is: `ghumantoo-dd45d`
    - Cloud Messaging is enabled (should see "Cloud Messaging API (Legacy)" or "Cloud Messaging API (V1)")

## Quick Diagnostic Test

Run this to check if FCM API is enabled:

1. Go to: https://console.cloud.google.com/apis/library/fcm.googleapis.com?project=ghumantoo-dd45d
2. If it says "API not enabled", click **ENABLE**
3. Wait 1-2 minutes
4. Try sending notification again

## Still Not Working?

If you've tried everything:

1. **Create a new test Firebase project**

    - Go to Firebase Console
    - Create new project
    - Enable Cloud Messaging
    - Generate new service account key
    - Test with new credentials
    - This will tell us if it's project-specific

2. **Check Firebase Status**

    - Go to: https://status.firebase.google.com/
    - Check if there are any service disruptions

3. **Contact Firebase Support**
    - If everything else fails, the issue might be on Firebase's side
    - Contact Firebase support with your project ID

## Expected Behavior After Fix

Once fixed, you should see in logs:

```
[INFO] FCM notification sent successfully
[INFO] FCM batch results processed {"sent": 1, "failed": 0}
```

And API response:

```json
{
    "success": true,
    "message": "General notification sent",
    "sent_count": 1,
    "failed_count": 0
}
```
