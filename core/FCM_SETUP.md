# FCM Push Notifications Setup Guide

## Overview

This guide explains how to set up Firebase Cloud Messaging (FCM) push notifications for the mobile app.

---

## Prerequisites

1. Firebase Project with Cloud Messaging enabled
2. Firebase service account credentials (JSON file)
3. Laravel application with FCM package installed

---

## Step 1: Obtain Firebase Service Account Credentials

### Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project or select an existing one
3. Enable Cloud Messaging:
    - Go to **Project Settings** > **Cloud Messaging**
    - Note your **Server Key** (legacy) or use service account

### Generate Service Account Key

1. Go to **Project Settings** > **Service Accounts**
2. Click **Generate New Private Key**
3. Download the JSON file (e.g., `firebase-service-account.json`)
4. **Important**: Keep this file secure - never commit it to version control

---

## Step 2: Place Credentials File

Place the downloaded JSON file in your Laravel storage directory:

```bash
# Recommended location
cp firebase-service-account.json storage/app/firebase-credentials.json
```

**Or** use any secure location and update the path in `.env`.

---

## Step 3: Configure Environment Variables

Add these variables to your `.env` file:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json
FCM_ANDROID_CHANNEL_ID=ghumantoo_default_channel
FCM_BATCH_SIZE=500
```

**Options:**

-   `FIREBASE_CREDENTIALS_PATH`: Path to your Firebase service account JSON file
    -   Relative path: `storage/app/firebase-credentials.json`
    -   Absolute path: `/full/path/to/firebase-credentials.json`
-   `FCM_ANDROID_CHANNEL_ID`: Android notification channel ID (must match mobile app)
-   `FCM_BATCH_SIZE`: Maximum tokens per batch (default: 500, max: 500)

---

## Step 4: Run Database Migration

```bash
php artisan migrate
```

This creates the `fcm_tokens` table to store device tokens.

---

## Step 5: Verify Installation

### Check Firebase Package

```bash
composer show kreait/firebase-php
```

### Test Firebase Connection

Create a test script or use Tinker:

```bash
php artisan tinker
```

```php
$factory = (new \Kreait\Firebase\Factory)->withServiceAccount(config('firebase.credentials_path'));
$messaging = $factory->createMessaging();
echo "Firebase initialized successfully!";
```

---

## Step 6: Test FCM Token Storage

### From Mobile App

The mobile app should call:

```
POST /api/users/fcm-token
```

With body:

```json
{
    "fcm_token": "your-fcm-token-here",
    "device_type": "android"
}
```

### Verify in Database

```bash
php artisan tinker
```

```php
\App\Models\FcmToken::count();
\App\Models\FcmToken::latest()->first();
```

---

## Step 7: Test Notification Sending

### Test via Firebase Console

1. Go to Firebase Console > **Cloud Messaging**
2. Click **Send test message**
3. Enter FCM token from database
4. Send notification

### Test via API (Admin Required)

Use Postman or curl:

```bash
curl -X POST http://localhost/api/notifications/send-general \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "title": "Test Notification",
    "message": "This is a test notification",
    "user_ids": [1, 2, 3]
  }'
```

---

## API Endpoints Reference

### 1. Store FCM Token

**Endpoint:** `POST /api/users/fcm-token`

**Request:**

```json
{
    "fcm_token": "dK3j2k...",
    "device_type": "android"
}
```

**Response:**

```json
{
    "success": true,
    "message": "FCM token stored successfully"
}
```

---

### 2. Delete FCM Token

**Endpoint:** `DELETE /api/users/fcm-token`

**Request (optional):**

```json
{
    "fcm_token": "dK3j2k..."
}
```

**Response:**

```json
{
    "success": true,
    "message": "FCM token removed successfully"
}
```

---

### 3. Send Release Notification

**Endpoint:** `POST /api/notifications/send-release`

**Auth:** Admin (Sanctum token or admin guard)

**Request:**

```json
{
    "version": "1.1.5",
    "title": "New Update Available!",
    "message": "Version 1.1.5 is now available with new features.",
    "release_notes": "• New features\n• Bug fixes",
    "update_url": "https://play.google.com/store/apps/details?id=..."
}
```

---

### 4. Send Promotional Notification

**Endpoint:** `POST /api/notifications/send-promotional`

**Auth:** Admin

**Request:**

```json
{
    "title": "Special Offer!",
    "message": "Get 20% off. Use code SAVE20",
    "coupon_code": "SAVE20",
    "expiry_date": "2025-12-31",
    "user_ids": [1, 2, 3]
}
```

---

### 5. Send Booking Notification

**Endpoint:** `POST /api/notifications/send-booking`

**Auth:** System token or admin token

**Request:**

```json
{
    "booking_id": "BK123456",
    "type": "confirmation",
    "title": "Booking Confirmed!",
    "message": "Your booking is confirmed. PNR: PNR123456",
    "user_id": 123,
    "passenger_phone": "9649240944"
}
```

---

### 6. Send General Notification

**Endpoint:** `POST /api/notifications/send-general`

**Auth:** Admin

**Request:**

```json
{
    "title": "Important Announcement",
    "message": "We're upgrading our services.",
    "deep_link": "Main/Home",
    "user_ids": [1, 2, 3],
    "priority": "high"
}
```

---

## Automatic Booking Notifications

Booking confirmation notifications are sent **automatically** when:

-   Payment is verified and booking status changes to confirmed (status = 1)
-   Sent to both booking owner and passenger (if different phones)

No manual API call needed - handled by `BookingService::verifyPaymentAndCompleteBooking()`.

---

## Troubleshooting

### Issue: "Firebase credentials file not found"

**Solution:**

-   Check `FIREBASE_CREDENTIALS_PATH` in `.env`
-   Verify file exists at the specified path
-   Check file permissions (should be readable by web server)

### Issue: "Firebase messaging not initialized"

**Solution:**

-   Verify credentials file is valid JSON
-   Check Firebase project has Cloud Messaging enabled
-   Ensure service account has proper permissions

### Issue: "Invalid FCM token"

**Solution:**

-   Tokens are automatically removed from database when invalid
-   Mobile app should refresh token and resend to `/api/users/fcm-token`
-   Check Firebase Console for token status

### Issue: "Notifications not received"

**Check:**

1. Token exists in database: `SELECT * FROM fcm_tokens WHERE user_id = ?`
2. Firebase logs in `storage/logs/laravel.log`
3. Mobile app notification permissions enabled
4. Android notification channel configured correctly

### Issue: "Admin authentication failed"

**Solution:**

-   For API: Use Sanctum bearer token (admin user)
-   For web: Use admin guard session
-   Check `NotificationController::checkAdminAuth()` implementation

---

## Security Considerations

1. **Never commit credentials file** to version control

    - Add to `.gitignore`: `storage/app/firebase-credentials.json`
    - Use environment variables for paths

2. **Protect admin endpoints**

    - All notification sending endpoints require admin authentication
    - Implement rate limiting to prevent spam

3. **Validate FCM tokens**

    - Tokens are validated before storing
    - Invalid tokens are automatically removed

4. **Log all notifications**
    - All notification attempts are logged
    - Check logs for debugging: `storage/logs/laravel.log`

---

## Performance Notes

-   **Batch sending**: Up to 500 tokens per batch (FCM limit)
-   **Automatic chunking**: Large broadcasts are split into batches
-   **Invalid token cleanup**: Invalid tokens are removed automatically
-   **Non-blocking**: Notification failures don't affect booking process

---

## Monitoring

### Check Notification Statistics

```sql
-- Count tokens by device type
SELECT device_type, COUNT(*) FROM fcm_tokens GROUP BY device_type;

-- Count tokens by user
SELECT user_id, COUNT(*) FROM fcm_tokens GROUP BY user_id;

-- Recent token registrations
SELECT * FROM fcm_tokens ORDER BY created_at DESC LIMIT 10;
```

### Check Laravel Logs

```bash
# View recent FCM logs
tail -f storage/logs/laravel.log | grep -i "fcm\|notification"
```

---

## Next Steps

1. ✅ Set up Firebase credentials
2. ✅ Configure environment variables
3. ✅ Run database migration
4. ✅ Test token storage from mobile app
5. ✅ Test notification sending
6. ✅ Configure Android notification channel in mobile app
7. ✅ Test booking confirmation notifications

---

## Support

For issues or questions:

-   Check `storage/logs/laravel.log` for error details
-   Verify Firebase project configuration
-   Ensure mobile app FCM setup matches backend configuration
