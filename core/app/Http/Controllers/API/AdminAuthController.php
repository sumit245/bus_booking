<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    /**
     * Admin API login endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Find admin by username
            $admin = Admin::where('username', $request->username)->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ], 401);
            }

            // Verify password
            if (!Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ], 401);
            }

            // Create Sanctum token
            $token = $admin->createToken('admin-api')->plainTextToken;

            Log::info('Admin API login successful', [
                'admin_id' => $admin->id,
                'username' => $admin->username,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'username' => $admin->username,
                        'email' => $admin->email,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin API login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login. Please try again.',
            ], 500);
        }
    }

    /**
     * Admin logout endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $admin = $request->user('sanctum');
            
            if ($admin) {
                // Revoke current token
                $request->user('sanctum')->currentAccessToken()->delete();
                
                Log::info('Admin API logout successful', [
                    'admin_id' => $admin->id,
                    'username' => $admin->username,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin API logout error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout.',
            ], 500);
        }
    }

    /**
     * Get authenticated admin profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        try {
            $admin = $request->user('sanctum');

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'username' => $admin->username,
                    'email' => $admin->email,
                    'image' => $admin->image,
                    'balance' => $admin->balance,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin API profile error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching profile.',
            ], 500);
        }
    }
}
