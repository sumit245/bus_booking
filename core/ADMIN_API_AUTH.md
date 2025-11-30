# Admin API Authentication Guide

## Overview

Admin API authentication has been enabled using Laravel Sanctum. Admins can now authenticate via API and receive bearer tokens for accessing protected endpoints.

## Changes Made

1. **Admin Model** - Added `HasApiTokens` trait to enable Sanctum token functionality
2. **AdminAuthController** - New controller with login, logout, and profile endpoints
3. **Routes** - Added admin authentication routes under `/api/admin/*`
4. **NotificationController** - Updated `checkAdminAuth()` to support Sanctum tokens

## API Endpoints

### 1. Admin Login
**Endpoint:** `POST /api/admin/login`

**Request Body:**
```json
{
    "username": "ghumantoobus",
    "password": "your_password"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "admin": {
            "id": 1,
            "name": "Rishi Shukla",
            "username": "ghumantoobus",
            "email": "info@vindhyashrisolutions.com"
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "token_type": "Bearer"
    }
}
```

**Error Response (401):**
```json
{
    "success": false,
    "message": "Invalid credentials."
}
```

### 2. Admin Profile
**Endpoint:** `GET /api/admin/profile`

**Headers:**
```
Authorization: Bearer {token_from_login}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Rishi Shukla",
        "username": "ghumantoobus",
        "email": "info@vindhyashrisolutions.com",
        "image": "6702b3e7a446d1728230375.png",
        "balance": "11640.00000000"
    }
}
```

### 3. Admin Logout
**Endpoint:** `POST /api/admin/logout`

**Headers:**
```
Authorization: Bearer {token_from_login}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully."
}
```

## Testing in Postman

### Step 1: Login
1. Create a new POST request
2. URL: `http://localhost/bus_booking/api/admin/login`
3. Body tab → raw → JSON:
   ```json
   {
       "username": "ghumantoobus",
       "password": "your_admin_password"
   }
   ```
4. Send request
5. Copy the `token` value from response

### Step 2: Use Token for Protected Endpoints
1. For any protected endpoint (like `/api/notifications/send-release`)
2. Go to Headers tab
3. Add header:
   - Key: `Authorization`
   - Value: `Bearer {paste_token_here}`
4. Send request

### Step 3: Test Notification Endpoints
Now you can test FCM notification endpoints using the admin token:

**Example - Send Release Notification:**
```
POST /api/notifications/send-release
Headers:
  Authorization: Bearer {your_admin_token}
  Content-Type: application/json

Body:
{
    "version": "1.2.0",
    "title": "New Update Available",
    "message": "Update your app to the latest version",
    "release_notes": "Bug fixes and improvements",
    "update_url": "https://play.google.com/store/apps/details?id=com.yourapp"
}
```

## Important Notes

1. **No Side Effects**: Adding Sanctum to Admin model does NOT affect existing admin web routes. Session-based authentication continues to work as before.

2. **Token Storage**: Tokens are stored in the `personal_access_tokens` table with `tokenable_type = App\Models\Admin`.

3. **Multiple Models**: Both `User` and `Admin` models now support Sanctum tokens. The system automatically identifies which model created a token based on the token's metadata.

4. **Security**: Tokens do not expire by default (as configured in `config/sanctum.php`). You can change this if needed.

## Troubleshooting

### Token not working?
- Ensure you're using `Bearer` prefix (with space) in Authorization header
- Check that token is copied completely (no truncation)
- Verify token was created for Admin model (check `personal_access_tokens` table)

### Getting 403 Unauthorized?
- Verify admin credentials are correct
- Check that token hasn't been revoked
- Ensure you're using the correct Authorization header format

### Getting 401 Unauthenticated?
- Token may be invalid or expired
- Try logging in again to get a new token
- Check Laravel logs for detailed error messages

