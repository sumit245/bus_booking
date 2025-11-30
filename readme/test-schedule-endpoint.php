<?php
// Simple test script to diagnose schedule endpoint issue

echo "Testing Schedule Endpoint\n";
echo "=========================\n\n";

// Test if the URL exists
$url = "http://localhost/bus_booking/operator/schedules/get-for-date?bus_id=1&route_id=1";
echo "Testing URL: $url\n\n";

// Initialize curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";
echo "$response\n";
