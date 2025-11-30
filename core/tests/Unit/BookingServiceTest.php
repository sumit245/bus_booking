<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\BookingService;
// Removed RefreshDatabase to prevent database issues - using DatabaseTransactions instead
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    // Using DatabaseTransactions instead of RefreshDatabase to prevent table drops
    // DatabaseTransactions rolls back changes after each test instead of dropping tables
    // use RefreshDatabase;

    protected $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }

    /**
     * Test Priority 1: Authenticated user ID provided → returns authenticated user
     */
    public function test_register_or_login_user_uses_authenticated_user_when_provided()
    {
        // Create test user A (booking owner)
        $userA = User::create([
            'firstname' => 'User',
            'lastname' => 'A',
            'mobile' => '9876543210',
            'email' => 'usera@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $requestData = [
            'authenticated_user_id' => $userA->id,
            'Phoneno' => '9999999999', // Different passenger phone (User B)
            'FirstName' => 'User',
            'LastName' => 'B',
        ];

        // Use reflection to call private method
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('registerOrLoginUser');
        $method->setAccessible(true);

        $result = $method->invoke($this->bookingService, $requestData);

        // Assert that User A is returned (booking owner), not User B
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($userA->id, $result->id);
        $this->assertEquals('9876543210', $result->mobile);
    }

    /**
     * Test Priority 2: Web session auth → returns session user
     */
    public function test_register_or_login_user_uses_web_session_when_authenticated()
    {
        // Create test user
        $user = User::create([
            'firstname' => 'Session',
            'lastname' => 'User',
            'mobile' => '9876543210',
            'email' => 'session@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Authenticate user in web session
        Auth::login($user);

        $requestData = [
            'Phoneno' => '9999999999',
            'FirstName' => 'Passenger',
            'LastName' => 'Name',
        ];

        // Use reflection to call private method
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('registerOrLoginUser');
        $method->setAccessible(true);

        $result = $method->invoke($this->bookingService, $requestData);

        // Assert that authenticated user is returned
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals('9876543210', $result->mobile);

        // Cleanup
        Auth::logout();
    }

    /**
     * Test Priority 3: No auth → creates/finds user by passenger phone
     */
    public function test_register_or_login_user_creates_user_by_passenger_phone_when_no_auth()
    {
        $requestData = [
            'Phoneno' => '9876543210',
            'FirstName' => 'Guest',
            'LastName' => 'User',
            'Email' => 'guest@test.com',
        ];

        // Ensure no authenticated user
        Auth::logout();

        // Use reflection to call private method
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('registerOrLoginUser');
        $method->setAccessible(true);

        $result = $method->invoke($this->bookingService, $requestData);

        // Assert that user was created/found by passenger phone
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('9876543210', $result->mobile);
        $this->assertEquals('Guest', $result->firstname);
    }

    /**
     * Test that authenticated user takes precedence over passenger phone
     */
    public function test_authenticated_user_takes_precedence_over_passenger_phone()
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

        // Create User B (passenger phone)
        $userB = User::create([
            'firstname' => 'Passenger',
            'lastname' => 'B',
            'mobile' => '9999999999',
            'email' => 'passenger@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $requestData = [
            'authenticated_user_id' => $userA->id, // User A is authenticated
            'Phoneno' => '9999999999', // User B's phone as passenger
        ];

        // Use reflection to call private method
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('registerOrLoginUser');
        $method->setAccessible(true);

        $result = $method->invoke($this->bookingService, $requestData);

        // Assert that User A (authenticated) is returned, not User B (passenger phone)
        $this->assertEquals($userA->id, $result->id);
        $this->assertNotEquals($userB->id, $result->id);
        $this->assertEquals('9876543210', $result->mobile);
    }

    /**
     * Test that invalid authenticated user ID falls back to other methods
     */
    public function test_invalid_authenticated_user_id_falls_back()
    {
        $requestData = [
            'authenticated_user_id' => 99999, // Non-existent user ID
            'Phoneno' => '9876543210',
            'FirstName' => 'Fallback',
            'LastName' => 'User',
        ];

        // Ensure no authenticated user
        Auth::logout();

        // Use reflection to call private method
        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('registerOrLoginUser');
        $method->setAccessible(true);

        $result = $method->invoke($this->bookingService, $requestData);

        // Assert that fallback logic created/found user by passenger phone
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('9876543210', $result->mobile);
    }

    /**
     * Test phone number normalization (removes country codes)
     */
    public function test_phone_number_normalization_removes_country_codes()
    {
        // Test with +91 prefix
        $requestData1 = [
            'Phoneno' => '+919876543210',
            'FirstName' => 'Test',
            'LastName' => 'User',
        ];

        Auth::logout();

        $reflection = new \ReflectionClass($this->bookingService);
        $method = $reflection->getMethod('registerOrLoginUser');
        $method->setAccessible(true);

        $result1 = $method->invoke($this->bookingService, $requestData1);
        $this->assertEquals('9876543210', $result1->mobile);

        // Test with 91 prefix (without +)
        $requestData2 = [
            'Phoneno' => '919876543211',
            'FirstName' => 'Test2',
            'LastName' => 'User2',
        ];

        $result2 = $method->invoke($this->bookingService, $requestData2);
        $this->assertEquals('9876543211', $result2->mobile);
    }
}

