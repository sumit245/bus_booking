# Testing Guide: Get My Tickets API

## Endpoint
```
POST /api/users/get-my-tickets
```

## Overview
This endpoint fetches tickets for a user. It supports two methods:
1. **Authenticated (Recommended)**: Uses bearer token from Sanctum authentication
2. **Legacy (Backward Compatible)**: Uses mobile_number parameter

The endpoint returns:
- All tickets booked by the user (as owner - can cancel)
- All tickets where user's phone matches passenger_phone (as passenger - cannot cancel)
- Each ticket includes `can_cancel` and `is_owner` flags

---

## Method 1: Authenticated Request (Recommended for Mobile App)

### Step 1: Get Authentication Token

**First, verify OTP to get token:**

**Endpoint:** `POST /api/verify-otp`

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/bus_booking/api/verify-otp`
3. Headers:
   ```
   Content-Type: application/json
   Accept: application/json
   ```
4. Body (raw JSON):
   ```json
   {
       "mobile_number": "9876543210",
       "otp": "123456",
       "user_name": "John Doe"
   }
   ```

**Response:**
```json
{
    "message": "Logged in successfully.",
    "status": 200,
    "data": {
        "user": {
            "id": 1,
            "mobile": "9876543210",
            ...
        },
        "token": "1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz"
    }
}
```

**Save the `token` value for next step!**

---

### Step 2: Get My Tickets (Authenticated)

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/bus_booking/api/users/get-my-tickets`
3. Headers:
   ```
   Content-Type: application/json
   Accept: application/json
   Authorization: Bearer YOUR_TOKEN_HERE
   ```
   Replace `YOUR_TOKEN_HERE` with the token from Step 1.

4. Body (raw JSON):
   ```json
   {}
   ```
   Or you can leave body empty - token is sufficient.

**Complete cURL Example:**
```bash
curl -X POST "http://localhost/bus_booking/api/users/get-my-tickets" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz" \
  -d '{}'
```

**Response Example:**
```json
{
    "success": true,
    "user": {
        "name": "John Doe",
        "mobile": "9876543210"
    },
    "tickets": [
        {
            "pnr_number": "PNR123456",
            "travel_name": "Sutra Seva",
            "bus_type": "AC Sleeper",
            "date_of_journey": "2025-11-28",
            "departure_time": "10:00 PM",
            "arrival_time": "06:00 AM",
            "duration": "08:00",
            "boarding_point": "Patna",
            "dropping_point": "Delhi",
            "passengers": [...],
            "total_amount": 1500.00,
            "status": "Booked",
            "booked_at": "2025-11-27 10:30:00",
            "booking_id": "BOOK123",
            "can_cancel": true,      // ✅ User can cancel (they're the owner)
            "is_owner": true,        // ✅ User is the booking owner
            "cancellation_details": null
        },
        {
            "pnr_number": "PNR789012",
            "travel_name": "Blue Lines",
            "bus_type": "Non-AC Seater",
            "date_of_journey": "2025-12-01",
            "departure_time": "08:00 PM",
            "arrival_time": "05:00 AM",
            "duration": "09:00",
            "boarding_point": "Mumbai",
            "dropping_point": "Pune",
            "passengers": [...],
            "total_amount": 800.00,
            "status": "Booked",
            "booked_at": "2025-11-25 14:20:00",
            "booking_id": "BOOK456",
            "can_cancel": false,     // ❌ User cannot cancel (they're a passenger)
            "is_owner": false,       // ❌ User is not the booking owner
            "cancellation_details": null
        }
    ]
}
```

---

## Method 2: Legacy Request (Backward Compatible)

**Note:** This method is for backward compatibility. Authenticated method is preferred.

**Postman Setup:**
1. Method: `POST`
2. URL: `http://localhost/bus_booking/api/users/get-my-tickets`
3. Headers:
   ```
   Content-Type: application/json
   Accept: application/json
   ```
4. Body (raw JSON):
   ```json
   {
       "mobile_number": "9876543210"
   }
   ```

**cURL Example:**
```bash
curl -X POST "http://localhost/bus_booking/api/users/get-my-tickets" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"mobile_number": "9876543210"}'
```

**Response:** Same format as Method 1.

---

## Understanding the Response Flags

### `can_cancel`
- **`true`**: User can cancel this ticket
  - User is the booking owner (`user_id` matches)
  - Ticket is not already cancelled
  - Ticket is not for a past journey
  - Ticket status is "Booked" (status = 1)

- **`false`**: User cannot cancel this ticket
  - User is a passenger (not the booking owner)
  - OR ticket is already cancelled
  - OR ticket is for a past journey

### `is_owner`
- **`true`**: User is the booking owner (`user_id` matches)
- **`false`**: User is a passenger (their phone matches `passenger_phone`)

---

## Mobile App Integration

### React Native / Flutter / Native App

#### 1. Authentication Flow

```javascript
// Step 1: Send OTP
const sendOTP = async (mobileNumber) => {
  const response = await fetch('http://your-domain.com/api/send-otp', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      mobile_number: mobileNumber
    })
  });
  return await response.json();
};

// Step 2: Verify OTP and Get Token
const verifyOTP = async (mobileNumber, otp, userName) => {
  const response = await fetch('http://your-domain.com/api/verify-otp', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      mobile_number: mobileNumber,
      otp: otp,
      user_name: userName || null
    })
  });
  const data = await response.json();
  
  // Save token securely (AsyncStorage, SecureStore, etc.)
  if (data.data && data.data.token) {
    await AsyncStorage.setItem('auth_token', data.data.token);
    await AsyncStorage.setItem('user_data', JSON.stringify(data.data.user));
    return data;
  }
  throw new Error('Token not received');
};
```

#### 2. Get My Tickets (Authenticated)

```javascript
// Get tickets using stored token
const getMyTickets = async () => {
  const token = await AsyncStorage.getItem('auth_token');
  
  if (!token) {
    throw new Error('Not authenticated. Please login first.');
  }

  const response = await fetch('http://your-domain.com/api/users/get-my-tickets', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}` // ✅ Use bearer token
    },
    body: JSON.stringify({}) // Empty body is fine
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  
  if (data.success) {
    return data.tickets; // Array of tickets
  } else {
    throw new Error(data.message || 'Failed to fetch tickets');
  }
};
```

#### 3. Display Tickets with Cancel Button

```javascript
const TicketListScreen = () => {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadTickets();
  }, []);

  const loadTickets = async () => {
    try {
      setLoading(true);
      const fetchedTickets = await getMyTickets();
      setTickets(fetchedTickets);
    } catch (error) {
      console.error('Error loading tickets:', error);
      Alert.alert('Error', error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleCancelTicket = (ticket) => {
    if (!ticket.can_cancel) {
      Alert.alert('Cannot Cancel', 'Only the booking owner can cancel this ticket.');
      return;
    }
    
    // Show confirmation dialog
    Alert.alert(
      'Cancel Ticket',
      `Are you sure you want to cancel ticket ${ticket.pnr_number}?`,
      [
        { text: 'No', style: 'cancel' },
        { 
          text: 'Yes', 
          onPress: () => {
            // Call cancel ticket API
            cancelTicket(ticket.booking_id, ticket.pnr_number);
          }
        }
      ]
    );
  };

  return (
    <View>
      {tickets.map((ticket) => (
        <TicketCard
          key={ticket.pnr_number}
          ticket={ticket}
          onCancel={() => handleCancelTicket(ticket)}
          showCancelButton={ticket.can_cancel} // ✅ Show cancel only if can_cancel is true
        />
      ))}
    </View>
  );
};
```

#### 4. Cancel Ticket

```javascript
const cancelTicket = async (bookingId, seatId) => {
  const token = await AsyncStorage.getItem('auth_token');
  
  const response = await fetch('http://your-domain.com/api/users/cancel-ticket', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}` // ✅ Required for authorization check
    },
    body: JSON.stringify({
      BookingId: bookingId,
      SeatId: seatId,
      SearchTokenId: 'your-search-token', // From booking response
      Remarks: 'Cancelled by user'
    })
  });

  const data = await response.json();
  
  if (data.success) {
    Alert.alert('Success', 'Ticket cancelled successfully');
    // Reload tickets
    loadTickets();
  } else {
    Alert.alert('Error', data.message || 'Failed to cancel ticket');
  }
};
```

---

## Postman Collection Setup

### Environment Variables
Create a Postman environment with:
```
BASE_URL: http://localhost/bus_booking
API_URL: {{BASE_URL}}/api
AUTH_TOKEN: (leave empty, will be set automatically)
MOBILE_NUMBER: 9876543210
```

### Request Sequence

1. **Send OTP**
   - Save OTP from response (manual step)

2. **Verify OTP**
   - URL: `{{API_URL}}/verify-otp`
   - In Tests tab, add:
     ```javascript
     if (pm.response.code === 200) {
         var jsonData = pm.response.json();
         if (jsonData.data && jsonData.data.token) {
             pm.environment.set("AUTH_TOKEN", jsonData.data.token);
         }
     }
     ```

3. **Get My Tickets**
   - URL: `{{API_URL}}/users/get-my-tickets`
   - Authorization: Bearer Token → `{{AUTH_TOKEN}}`
   - Body: `{}`

---

## Testing Scenarios

### Scenario 1: User A Books Ticket for User B

1. **User A** books ticket with **User B's** phone as passenger
2. **User A** (booking owner):
   - Can see ticket in their list
   - `can_cancel: true`
   - `is_owner: true`
   - Can cancel the ticket ✅

3. **User B** (passenger):
   - Can see ticket in their list (via passenger_phone match)
   - `can_cancel: false`
   - `is_owner: false`
   - Cannot cancel the ticket ❌
   - Can view, print, share on WhatsApp ✅

### Scenario 2: User Views Own Tickets

- All tickets show `is_owner: true`
- Active tickets show `can_cancel: true`
- Cancelled/past tickets show `can_cancel: false`

### Scenario 3: User Views Tickets as Passenger

- Tickets show `is_owner: false`
- All tickets show `can_cancel: false` (regardless of status)

---

## Error Responses

### 404 - User Not Found
```json
{
    "success": false,
    "message": "User not found. Please authenticate or provide valid mobile_number."
}
```
**Solution:** 
- For authenticated: Ensure token is valid
- For legacy: Ensure mobile_number exists in database

### 403 - Unauthorized Cancellation
```json
{
    "success": false,
    "message": "Unauthorized: Only the booking owner can cancel this ticket. Passengers cannot cancel tickets.",
    "error": "UNAUTHORIZED"
}
```
**Solution:** User is trying to cancel a ticket they don't own.

---

## Quick Test Checklist

- [ ] Get token via `/api/verify-otp`
- [ ] Get tickets with bearer token (should return all tickets)
- [ ] Check `can_cancel` flag (true for owner, false for passenger)
- [ ] Check `is_owner` flag (true for owner, false for passenger)
- [ ] Try cancel ticket as owner (should succeed)
- [ ] Try cancel ticket as passenger (should fail with 403)
- [ ] Test legacy method with mobile_number (backward compatibility)

---

## Notes

1. **Token Storage**: Store token securely (use secure storage in mobile apps)
2. **Token Expiry**: Sanctum tokens don't expire by default, but you can configure expiry
3. **Mobile Number Format**: Must be 10 digits, starting with 6-9 (Indian format)
4. **Backward Compatibility**: Legacy method (mobile_number) still works for existing apps
5. **WhatsApp Messages**: Only passenger phone receives WhatsApp notifications, not booking owner

---

## Troubleshooting

### Token Not Working
- Check if token is correctly set in Authorization header
- Format: `Authorization: Bearer YOUR_TOKEN`
- Ensure no extra spaces or quotes

### No Tickets Returned
- User might not have any tickets
- Check if user_id or passenger_phone matches in database
- Check ticket status (all statuses are returned)

### Can't Cancel Ticket
- Check `can_cancel` flag (must be `true`)
- Verify user is the booking owner (`is_owner: true`)
- Ensure ticket is not already cancelled
- Ensure ticket is not for past journey

