# Referral API Documentation

## Base URL

```
https://yourdomain.com/api
```

## Endpoints

### 1. Get Referral Data

**Endpoint:** `GET /api/users/referral-data`

**Query Parameters:**

- `mobile_number` (required): User's mobile number

**Example Request:**

```bash
curl "https://yourdomain.com/api/users/referral-data?mobile_number=9876543210"
```

**Response:**

```json
{
  "success": true,
  "referralCode": "ABC123",
  "rewardPercentage": 10,
  "shareMessage": "Join Ghumantoo and get amazing bus booking deals!",
  "referralLink": "https://yourdomain.com?ref=ABC123"
}
```

---

### 2. Get Referral Statistics

**Endpoint:** `GET /api/users/referral-stats`

**Query Parameters:**

- `mobile_number` (required): User's mobile number

**Example Request:**

```bash
curl "https://yourdomain.com/api/users/referral-stats?mobile_number=9876543210"
```

**Response:**

```json
{
  "success": true,
  "totalReferrals": 12,
  "successfulInstalls": 8,
  "totalEarnings": 450.5,
  "pendingEarnings": 120.0
}
```

---

### 3. Get Referral History

**Endpoint:** `GET /api/users/referral-history`

**Query Parameters:**

- `mobile_number` (required): User's mobile number
- `limit` (optional): Number of records to return (default: 10, max: 100)

**Example Request:**

```bash
curl "https://yourdomain.com/api/users/referral-history?mobile_number=9876543210&limit=10"
```

**Response:**

```json
{
  "success": true,
  "recentReferrals": [
    {
      "id": 1,
      "name": "Raj Kumar",
      "date": "2025-11-20",
      "status": "Completed",
      "earning": 50
    },
    {
      "id": 2,
      "name": "Priya Singh",
      "date": "2025-11-19",
      "status": "Pending",
      "earning": 75
    }
  ]
}
```

**Status Values:**

- `Completed` - Reward has been credited
- `Pending` - Awaiting credit period
- `Failed` - Booking was cancelled/refunded

---

### 4. Record Installation

**Endpoint:** `POST /api/referral/install`

**Request Body:**

```json
{
  "referral_code": "ABC123",
  "device_id": "unique-device-id",
  "source": "app"
}
```

**Parameters:**

- `referral_code` (required): 6-character referral code
- `device_id` (optional): Unique device identifier
- `source` (optional): One of `app`, `pwa`, `web` (default: `app`)

**Example Request:**

```bash
curl -X POST "https://yourdomain.com/api/referral/install" \
  -H "Content-Type: application/json" \
  -d '{
    "referral_code": "ABC123",
    "device_id": "device-uuid-123",
    "source": "app"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Install recorded successfully",
  "event_id": 123
}
```

---

### 5. Record Signup (Optional - Use After OTP Verification)

**Endpoint:** `POST /api/referral/signup`

**Request Body:**

```json
{
  "referral_code": "ABC123",
  "mobile_number": "9876543210"
}
```

**Example Request:**

```bash
curl -X POST "https://yourdomain.com/api/referral/signup" \
  -H "Content-Type: application/json" \
  -d '{
    "referral_code": "ABC123",
    "mobile_number": "9876543210"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Signup recorded successfully",
  "event_id": 456
}
```

---

### 6. Get Referral Settings (Public)

**Endpoint:** `GET /api/referral/settings`

**Example Request:**

```bash
curl "https://yourdomain.com/api/referral/settings"
```

**Response:**

```json
{
  "success": true,
  "is_enabled": true,
  "reward_type": "percent_of_ticket",
  "reward_percentage": 10,
  "min_booking_amount": 100,
  "share_message": "Join Ghumantoo and get amazing bus booking deals!",
  "terms_and_conditions": "..."
}
```

---

## Implementation Flow

### Step 1: App Install

When user installs the app with referral link:

1. Extract `ref` parameter from installation link
2. Call `POST /api/referral/install` with the code

### Step 2: User Signup

After OTP verification during signup:

1. Retrieve stored referral code (from install or landing page)
2. Call `POST /api/referral/signup` with code and mobile number

### Step 3: Display Referral Code

After user logs in:

1. Call `GET /api/users/referral-data` with mobile number
2. Display the `referralCode` and `referralLink`
3. Show `shareMessage` in share dialog

### Step 4: Show Stats & History

On referral screen:

1. Call `GET /api/users/referral-stats` to show earnings
2. Call `GET /api/users/referral-history` to show list of referrals

---

## Error Handling

All endpoints return consistent error format:

**Validation Error (422):**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "mobile_number": ["The mobile number field is required."]
  }
}
```

**Not Found (404):**

```json
{
  "success": false,
  "message": "User not found"
}
```

**Bad Request (400):**

```json
{
  "success": false,
  "message": "Invalid referral code"
}
```

**Server Error (500):**

```json
{
  "success": false,
  "message": "Failed to get referral data"
}
```

---

## Testing

**Test Referral Code:** Create a test user and get their code via API

**Test Flow:**

1. Create user A (mobile: 9999999999)
2. Get referral code: `GET /api/users/referral-data?mobile_number=9999999999`
3. Use code to install as user B: `POST /api/referral/install`
4. Signup user B with code: `POST /api/referral/signup`
5. Check user A's stats: `GET /api/users/referral-stats?mobile_number=9999999999`

---

## Notes

- Referral codes are **6-character alphanumeric** (e.g., `ABC123`)
- Codes are **case-insensitive** but stored in uppercase
- **Self-referral is blocked** - users cannot refer themselves
- Rewards are credited based on admin settings (immediate or delayed)
- First booking triggers reward calculation (if enabled by admin)
- Duplicate installs from same device are prevented
