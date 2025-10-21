<?php

/**
 * Debug script to test operator bus seat layout API
 * Usage: php debug_api_seats.php
 */

require_once __DIR__ . '/core/vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\API\ApiTicketController;

// Bootstrap Laravel app
$app = require_once __DIR__ . '/core/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== BUS BOOKING SYSTEM - API SEAT LAYOUT DEBUG ===\n\n";

// Test data for Patna (9292) -> Delhi (230)
$testCases = [
    [
        'name' => 'Third Party Bus Example',
        'SearchTokenId' => 'TEST_TOKEN_123',
        'ResultIndex' => 'TB_1234', // Third party bus
    ],
    [
        'name' => 'Operator Bus Example 1',
        'SearchTokenId' => 'TEST_TOKEN_456',
        'ResultIndex' => 'OP_1', // Operator bus ID 1
    ],
    [
        'name' => 'Operator Bus Example 2',
        'SearchTokenId' => 'TEST_TOKEN_789',
        'ResultIndex' => 'OP_2', // Operator bus ID 2
    ],
];

echo "1. Checking database for operator buses...\n";

// Check if operator buses exist
try {
    $operatorBuses = DB::table('operator_buses')
        ->join('seat_layouts', 'operator_buses.id', '=', 'seat_layouts.operator_bus_id')
        ->where('seat_layouts.is_active', true)
        ->where('seat_layouts.html_layout', '!=', '')
        ->select(
            'operator_buses.id',
            'operator_buses.travel_name',
            'operator_buses.bus_type',
            'seat_layouts.id as layout_id',
            'seat_layouts.total_seats',
            DB::raw('LENGTH(seat_layouts.html_layout) as html_length')
        )
        ->get();

    if ($operatorBuses->count() > 0) {
        echo "✅ Found " . $operatorBuses->count() . " operator buses with seat layouts:\n";
        foreach ($operatorBuses as $bus) {
            echo "   - Bus ID: {$bus->id}, Name: {$bus->travel_name}, Type: {$bus->bus_type}\n";
            echo "     Layout ID: {$bus->layout_id}, Seats: {$bus->total_seats}, HTML Length: {$bus->html_length}\n";
        }
    } else {
        echo "❌ No operator buses found with active seat layouts!\n";
        echo "   Please ensure:\n";
        echo "   - Operator buses exist in 'operator_buses' table\n";
        echo "   - They have active seat layouts in 'seat_layouts' table\n";
        echo "   - HTML layout is not empty\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. Testing API endpoint directly...\n";

// Create controller instance
$controller = new ApiTicketController(
    app(\App\Services\BusService::class),
    app(\App\Services\BookingService::class)
);

foreach ($testCases as $index => $testCase) {
    echo "\n--- Test Case " . ($index + 1) . ": {$testCase['name']} ---\n";
    echo "ResultIndex: {$testCase['ResultIndex']}\n";
    echo "SearchTokenId: {$testCase['SearchTokenId']}\n";

    // Create mock request
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'SearchTokenId' => $testCase['SearchTokenId'],
        'ResultIndex' => $testCase['ResultIndex']
    ]);

    try {
        // Call the API method
        $response = $controller->showSeat($request);
        $responseData = json_decode($response->getContent(), true);
        $statusCode = $response->getStatusCode();

        echo "Status Code: {$statusCode}\n";

        if ($statusCode === 200) {
            echo "✅ SUCCESS!\n";
            echo "Response Keys: " . implode(', ', array_keys($responseData)) . "\n";

            if (isset($responseData['html'])) {
                $seatCount = is_array($responseData['html']) ? count($responseData['html']) : 0;
                echo "Seat Layout Parsed: {$seatCount} items\n";
            }

            if (isset($responseData['availableSeats'])) {
                echo "Available Seats: {$responseData['availableSeats']}\n";
            }
        } else {
            echo "❌ FAILED!\n";
            echo "Error: " . ($responseData['error'] ?? 'Unknown error') . "\n";

            if (isset($responseData['details'])) {
                echo "Details: " . json_encode($responseData['details']) . "\n";
            }
        }

    } catch (\Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "\n3. Testing parseSeatHtmlToJson helper directly...\n";

// Test the helper function directly
if ($operatorBuses->count() > 0) {
    $firstBus = $operatorBuses->first();

    try {
        $seatLayout = DB::table('seat_layouts')
            ->where('id', $firstBus->layout_id)
            ->first();

        if ($seatLayout && $seatLayout->html_layout) {
            echo "Testing with Bus ID {$firstBus->id} layout...\n";
            echo "HTML Length: " . strlen($seatLayout->html_layout) . "\n";
            echo "HTML Preview: " . substr($seatLayout->html_layout, 0, 100) . "...\n";

            $startTime = microtime(true);
            $parsedLayout = parseSeatHtmlToJson($seatLayout->html_layout);
            $endTime = microtime(true);

            $executionTime = ($endTime - $startTime) * 1000;

            echo "✅ Helper function executed successfully!\n";
            echo "Execution Time: " . number_format($executionTime, 2) . " ms\n";
            echo "Parsed Layout Type: " . gettype($parsedLayout) . "\n";

            if (is_array($parsedLayout)) {
                echo "Parsed Layout Keys: " . implode(', ', array_keys($parsedLayout)) . "\n";

                if (isset($parsedLayout['seat'])) {
                    $upperRows = $parsedLayout['seat']['upper_deck']['rows'] ?? [];
                    $lowerRows = $parsedLayout['seat']['lower_deck']['rows'] ?? [];
                    echo "Upper Deck Rows: " . count($upperRows) . "\n";
                    echo "Lower Deck Rows: " . count($lowerRows) . "\n";
                }
            }
        }

    } catch (\Exception $e) {
        echo "❌ Helper function failed: " . $e->getMessage() . "\n";
    }
}

echo "\n4. Checking Laravel logs...\n";
echo "Please check these log files for detailed debugging info:\n";
echo "- storage/logs/laravel.log\n";
echo "- Look for entries containing 'API showSeat' or 'handleOperatorBusSeatLayout'\n";

echo "\n=== DEBUG COMPLETE ===\n";
echo "If operator buses are failing, check:\n";
echo "1. Database has operator buses with active seat layouts\n";
echo "2. HTML layout is valid and not empty\n";
echo "3. parseSeatHtmlToJson helper is working correctly\n";
echo "4. No exceptions in the controller logic\n";
echo "5. Laravel logs for detailed error information\n";
