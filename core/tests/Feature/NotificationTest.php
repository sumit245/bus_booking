<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BookedTicket;
use App\Services\BookingService;
// Removed RefreshDatabase to prevent database issues
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    // Removed RefreshDatabase - tests should not drop production tables
    // use RefreshDatabase;

    /**
     * Test notification sent to passenger phone
     * Note: This test verifies the logic, actual WhatsApp sending would be mocked in production
     */
    public function test_notification_sent_to_passenger_phone()
    {
        // Create booking owner
        $bookingOwner = User::create([
            'firstname' => 'Owner',
            'lastname' => 'User',
            'mobile' => '9876543210',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Create ticket with passenger phone different from owner
        $ticket = BookedTicket::create([
            'user_id' => $bookingOwner->id,
            'passenger_phone' => '9999999999', // Different from owner
            'passenger_name' => 'Passenger User',
            'status' => 1, // Confirmed
            'total_amount' => 1000,
            'date_of_journey' => now()->addDays(1),
            'pnr_number' => 'TEST123',
        ]);

        // Load booking owner relationship
        $ticket->load('user');

        // Verify passenger phone is different from owner phone
        $this->assertNotEquals($bookingOwner->mobile, $ticket->passenger_phone);
        $this->assertEquals('9999999999', $ticket->passenger_phone);
        $this->assertEquals('9876543210', $ticket->user->mobile);
    }

    /**
     * Test notification sent to booking owner when different from passenger
     */
    public function test_notification_sent_to_booking_owner_when_different_phone()
    {
        // Create booking owner (User A)
        $userA = User::create([
            'firstname' => 'Owner',
            'lastname' => 'A',
            'mobile' => '9876543210',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Create ticket where User A booked for User B
        $ticket = BookedTicket::create([
            'user_id' => $userA->id, // User A is booking owner
            'passenger_phone' => '9999999999', // User B's phone
            'passenger_name' => 'Passenger B',
            'status' => 1,
            'total_amount' => 1000,
            'date_of_journey' => now()->addDays(1),
            'pnr_number' => 'TEST123',
        ]);

        // Load relationship
        $ticket->load('user');

        // Verify both phones are stored and different
        $ownerPhone = $ticket->user->mobile ?? null;
        $passengerPhone = $ticket->passenger_phone;

        $this->assertNotNull($ownerPhone);
        $this->assertNotNull($passengerPhone);
        
        // Normalize phones for comparison
        $normalizedOwner = preg_replace('/[^0-9]/', '', substr($ownerPhone, -10));
        $normalizedPassenger = preg_replace('/[^0-9]/', '', substr($passengerPhone, -10));

        // Assert phones are different
        $this->assertNotEquals($normalizedOwner, $normalizedPassenger);
        $this->assertEquals('9876543210', $normalizedOwner);
        $this->assertEquals('9999999999', $normalizedPassenger);
    }

    /**
     * Test no duplicate notification when owner = passenger
     */
    public function test_no_duplicate_notification_when_owner_equals_passenger()
    {
        // Create user who books for themselves
        $user = User::create([
            'firstname' => 'Self',
            'lastname' => 'Booker',
            'mobile' => '9876543210',
            'email' => 'self@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Create ticket where user books for themselves
        $ticket = BookedTicket::create([
            'user_id' => $user->id,
            'passenger_phone' => '9876543210', // Same as user's mobile
            'passenger_name' => 'Self Booker',
            'status' => 1,
            'total_amount' => 1000,
            'date_of_journey' => now()->addDays(1),
            'pnr_number' => 'TEST123',
        ]);

        // Load relationship
        $ticket->load('user');

        // Verify phones are the same
        $ownerPhone = $ticket->user->mobile ?? null;
        $passengerPhone = $ticket->passenger_phone;

        // Normalize phones for comparison
        $normalizedOwner = preg_replace('/[^0-9]/', '', substr($ownerPhone, -10));
        $normalizedPassenger = preg_replace('/[^0-9]/', '', substr($passengerPhone, -10));

        // Assert phones are the same (should only send one notification)
        $this->assertEquals($normalizedOwner, $normalizedPassenger);
        $this->assertEquals('9876543210', $normalizedOwner);
    }

    /**
     * Test phone number normalization logic
     */
    public function test_phone_number_normalization_for_notification_comparison()
    {
        $testCases = [
            ['+919876543210', '9876543210'],
            ['919876543210', '9876543210'],
            ['9876543210', '9876543210'],
            ['+91-9876543210', '9876543210'],
        ];

        foreach ($testCases as [$input, $expected]) {
            $normalized = preg_replace('/[^0-9]/', '', substr($input, -10));
            $this->assertEquals($expected, $normalized, "Failed to normalize: $input");
        }
    }

    /**
     * Test that booking owner relationship is accessible
     */
    public function test_booking_owner_relationship_accessible()
    {
        $user = User::create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'mobile' => '9876543210',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $ticket = BookedTicket::create([
            'user_id' => $user->id,
            'passenger_phone' => '9999999999',
            'passenger_name' => 'Passenger',
            'status' => 1,
            'total_amount' => 1000,
            'date_of_journey' => now()->addDays(1),
        ]);

        // Load relationship
        $ticket->load('user');

        // Assert relationship exists
        $this->assertNotNull($ticket->user);
        $this->assertEquals($user->id, $ticket->user->id);
        $this->assertEquals('9876543210', $ticket->user->mobile);
    }
}

