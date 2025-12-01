# Fixing FCM ConnectException Error

## Current Error

You're now getting a **`ConnectException`** which means your server **cannot connect** to Firebase servers. This is a **network connectivity issue**.

## What Changed

-   ❌ Previous error: `"invalid_grant"` (authentication error)
-   ❌ Current error: `ConnectException` (network connectivity error)

This suggests:

-   ✅ Your credentials might now be working (we're past authentication)
-   ❌ But the server can't reach Firebase servers over the network

## Quick Checks

### 1. Test Internet Connectivity

```bash
# Test basic connectivity
ping -c 3 google.com

# Test HTTPS to Firebase
curl -I https://fcm.googleapis.com
```

### 2. Test from PHP

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/bus_booking/core
php -r "echo file_get_contents('https://www.google.com') ? 'Connected' : 'Failed';"
```

### 3. Check PHP Extensions

```bash
php -m | grep -i "curl\|openssl"
# Should show: curl, openssl
```

## Common Causes (macOS/XAMPP)

### 1. macOS Firewall Blocking

**Check firewall status:**

```bash
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate
```

**Temporarily disable to test:**

```bash
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate off
```

**Add XAMPP/PHP to firewall exceptions:**

1. System Preferences > Security & Privacy > Firewall
2. Click "Firewall Options"
3. Ensure XAMPP/PHP is allowed

### 2. Network Proxy Settings

If you're behind a proxy, configure PHP to use it:

Check if you have proxy settings:

```bash
echo $http_proxy
echo $https_proxy
```

### 3. DNS Resolution

**Test DNS:**

```bash
nslookup fcm.googleapis.com
```

**If DNS fails, use Google DNS:**

1. System Preferences > Network
2. Advanced > DNS
3. Add: `8.8.8.8` and `8.8.4.4`

### 4. SSL Certificate Issues

**Update certificates:**

```bash
# macOS uses system certificates
# Check if they're up to date
brew update && brew upgrade ca-certificates  # If using Homebrew
```

### 5. XAMPP Network Configuration

Check XAMPP's network settings - ensure it can make outbound connections.

## Immediate Fix to Try

Since the connection test (`curl`) works, try this:

1. **Check if it's a timeout issue:**

    - The connection might be timing out
    - Firebase API calls can take a few seconds

2. **Check PHP timeout settings:**

    - Increase `max_execution_time` in `php.ini`
    - Increase timeout in Firebase SDK if possible

3. **Try sending a single notification:**
    - Instead of batch, try one token at a time
    - This might help identify if it's a timeout/batch issue

## Still Getting ConnectException?

The error suggests Firebase SDK can't complete the connection. This could be:

1. **Firebase SDK timeout too short**
2. **Network interruption during API call**
3. **SSL/TLS handshake failure**

Try increasing timeouts or check network stability.
