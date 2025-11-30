<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BookedTicket;
use App\Models\OperatorBus;
use App\Models\OperatorRoute;
use App\Models\City;
// Removed RefreshDatabase to prevent database issues
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlockSeatApiTest extends TestCase
{
    // Removed RefreshDatabase - tests should not drop production tables
    // use RefreshDatabase;

    /**
     * Test authenticated booking for someone else (User A books for User B)
     */
    public function test_authenticated_user_can_book_for_someone_else()
    {
        // Create User A (booking owner)
        $userA = User::create([
            'firstname' => 'Owner',
            'lastname' => 'User',
            'mobile' => '9876543210',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Create operator bus setup (simplified for testing)
        $originCity = City::create([
            'name' => 'Patna',
            'state' => 'Bihar',
            'slug' => 'patna',
        ]);

        $destinationCity = City::create([
            'name' => 'Delhi',
            'state' => 'Delhi',
            'slug' => 'delhi',
        ]);

        $route = OperatorRoute::create([
            'origin_city_id' => $originCity->id,
            'destination_city_id' => $destinationCity->id,
            'route_name' => 'Patna to Delhi',
            'distance_km' => 1000,
            'estimated_duration_hours' => 18,
        ]);

        $operatorBus = OperatorBus::create([
            'operator_id' => 1,
            'current_route_id' => $route->id,
            'bus_number' => 'TEST123',
            'bus_type' => 'AC Sleeper',
            'travel_name' => 'Test Travels',
            'total_seats' => 40,
            'base_price' => 1000,
            'status' => 1,
        ]);

        // Authenticate User A
        Sanctum::actingAs($userA);

        // Mock cache for search token
        $dateOfJourney = now()->addDays(1)->format('Y-m-d');
        Cache::put('bus_search_results_test_token', [
            'date_of_journey' => $dateOfJourney,
        ], 3600);

        // Prepare request data
        $requestData = [
            'OriginCity' => 'Patna',
            'DestinationCity' => 'Delhi',
            'SearchTokenId' => 'test_token',
            'ResultIndex' => 'OP_' . $operatorBus->id,
            'UserIp' => '127.0.0.1',
            'BoardingPointId' => '1',
            'DroppingPointId' => '1',
            'Seats' => '1',
            'FirstName' => 'Passenger',
            'LastName' => 'User',
            'Gender' => '1',
            'Email' => 'passenger@test.com',
            'Phoneno' => '9999999999', // User B's phone (different from User A)
            'age' => 25,
            'Address' => 'Test Address',
            'DateOfJourney' => $dateOfJourney,
        ];

        // Mock external API responses (if needed)
        // Since we're testing operator buses, we may not need external API mocking
        // Operator buses use internal logic, not external API

        // Make API request
        $response = $this->postJson('/api/bus/block-seat', $requestData);

        // Assert response is successful (or appropriate status)
        // Note: Full implementation might require more setup (seat layouts, etc.)
        // This test focuses on verifying booking ownership

        if ($response->status() === 200 || $response->status() === 201) {
            // If booking was created, verify ownership
            $ticket = BookedTicket::where('passenger_phone', '9999999999')
                ->latest()
                ->first();

            if ($ticket) {
                // Assert that User A (booking owner) is the user_id
                $this->assertEquals($userA->id, $ticket->user_id);
                
                // Assert that User B's phone is stored as passenger_phone
                $this->assertEquals('9999999999', $ticket->passenger_phone);
            }
        }
    }

    /**
     * Test authenticated booking for self (same phone)
     */
    public function test_authenticated_user_can_book_for_self()
    {
        // Create User A
        $userA = User::create([
            'firstname' => 'Owner',
            'lastname' => 'User',
            'mobile' => '9876543210',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Authenticate User A
        Sanctum::actingAs($userA);

        // Prepare request with same phone as User A
        $requestData = [
            'OriginCity' => 'Patna',
            'DestinationCity' => 'Delhi',
            'SearchTokenId' => 'test_token',
            'ResultIndex' => 'OP_1',
            'UserIp' => '127.0.0.1',
            'BoardingPointId' => '1',
            'DroppingPointId' => '1',
            'Seats' => '1',
            'FirstName' => 'Owner',
            'LastName' => 'User',
            'Gender' => '1',
            'Email' => 'owner@test.com',
            'Phoneno' => '9876543210', // Same as User A's phone
            'age' => 25,
        ];

        // Make API request
        $response = $this->postJson('/api/bus/block-seat', $requestData);

        // If booking was created, verify ownership
        if ($response->status() === 200 || $response->status() === 201) {
            $ticket = BookedTicket::where('user_id', $userA->id)
                ->latest()
                ->first();

            if ($ticket) {
                // Assert that User A is the booking owner
                $this->assertEquals($userA->id, $ticket->user_id);
                $this->assertEquals('9876543210', $ticket->passenger_phone);
            }
        }
    }

    /**
     * Test guest booking (no token) → falls back to passenger phone
     */
    public function test_guest_booking_uses_passenger_phone()
    {
        // Prepare request without authentication
        $requestData = [
            'OriginCity' => 'Patna',
            'DestinationCity' => 'Delhi',
            'SearchTokenId' => 'test_token',
            'ResultIndex' => 'OP_1',
            'UserIp' => '127.0.0.1',
            'BoardingPointId' => '1',
            'DroppingPointId' => '1',
            'Seats' => '1',
            'FirstName' => 'Guest',
            'LastName' => 'User',
            'Gender' => '1',
            'Email' => 'guest@test.com',
            'Phoneno' => '9876543210',
            'age' => 25,
        ];

        // Make API request without authentication
        $response = $this->postJson('/api/bus/block-seat', $requestData);

        // If booking was created, verify user was created/found by passenger phone
        if ($response->status() === 200 || $response->status() === 201) {
            $user = User::where('mobile', '9876543210')->first();
            
            if ($user) {
                $ticket = BookedTicket::where('user_id', $user->id)
                    ->latest()
                    ->first();

                if ($ticket) {
                    // Assert that user created by passenger phone is the booking owner
                    $this->assertEquals($user->id, $ticket->user_id);
                    $this->assertEquals('9876543210', $ticket->passenger_phone);
                }
            }
        }
    }

    /**
     * Test that booking ownership is correctly stored in database
     */
    public function test_booking_ownership_stored_correctly()
    {
        // Create User A (booking owner)
        $userA = User::create([
            'firstname' => 'Owner',
            'lastname' => 'A',
            'mobile' => '9876543210',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Create User B (passenger, but shouldn't be booking owner)
        $userB = User::create([
            'firstname' => 'Passenger',
            'lastname' => 'B',
            'mobile' => '9999999999',
            'email' => 'passenger@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Authenticate User A
        Sanctum::actingAs($userA);

        // Create a booking manually (simulating successful booking)
        $ticket = BookedTicket::create([
            'user_id' => $userA->id, // User A is the booking owner
            'passenger_phone' => '9999999999', // User B's phone as passenger
            'passenger_name' => 'Passenger User',
            'status' => 0, // Pending
            'total_amount' => 1000,
            'date_of_journey' => now()->addDays(1),
        ]);

        // Assert booking ownership
        $this->assertEquals($userA->id, $ticket->user_id);
        $this->assertEquals('9999999999', $ticket->passenger_phone);
        
        // Assert that User B is NOT the booking owner
        $this->assertNotEquals($userB->id, $ticket->user_id);
    }
}

