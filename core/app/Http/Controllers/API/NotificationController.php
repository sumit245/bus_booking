<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\User;
use App\Models\BookedTicket;
use App\Services\FcmNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FcmNotificationService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Check admin authentication (supports both Sanctum and admin guard)
     *
     * @param Request $request
     * @return \App\Models\Admin|null
     */
    protected function checkAdminAuth(Request $request)
    {
        // Check Sanctum token first (for API authentication)
        if ($request->bearerToken()) {
            $admin = $request->user('sanctum');
            // Verify that the authenticated user is an Admin model instance
            if ($admin instanceof \App\Models\Admin) {
                return $admin;
            }
        }

        // Check admin guard (for web session authentication)
        if (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        }
        
        return null;
    }

    /**
     * Send release notification to all users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendReleaseNotification(Request $request)
    {
        try {
            $admin = $this->checkAdminAuth($request);
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            $request->validate([
                'version' => 'required|string',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'release_notes' => 'nullable|string',
                'update_url' => 'required|url',
            ]);

            $title = $request->title;
            $body = $request->message;
            $data = [
                'type' => 'release',
                'notification_type' => 'release',
                'deep_link' => 'Main/Home',
                'version' => $request->version,
                'release_notes' => $request->release_notes ?? '',
                'update_url' => $request->update_url,
            ];

            $totalTokens = FcmToken::count();
            $results = $this->fcmService->sendToAll($title, $body, $data);

            Log::info('Release notification sent', [
                'admin_id' => $admin->id,
                'version' => $request->version,
                'total_tokens' => $totalTokens,
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ]);

            $message = $results['sent'] > 0 
                ? "Release notification sent to {$results['sent']} users"
                : ($totalTokens == 0 
                    ? "No FCM tokens found in the system. Users need to register their FCM tokens first."
                    : "No notifications were sent. All tokens may be invalid or Firebase service issue.");

            return response()->json([
                'success' => true,
                'message' => $message,
                'sent_count' => $results['sent'],
                'failed_count' => $results['failed'],
                'total_tokens_found' => $totalTokens
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send release notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send release notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send promotional notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPromotionalNotification(Request $request)
    {
        try {
            $admin = $this->checkAdminAuth($request);
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'offer_id' => 'nullable|integer',
                'coupon_code' => 'nullable|string',
                'expiry_date' => 'nullable|date',
                'deep_link' => 'nullable|string',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'integer|exists:users,id',
                'image_url' => 'nullable|url',
            ]);

            $title = $request->title;
            $body = $request->message;
            $deepLink = $request->deep_link ?? 'Main/Home';
            
            $data = [
                'type' => 'promotional',
                'notification_type' => 'promotional',
                'deep_link' => $deepLink,
            ];

            if ($request->offer_id) {
                $data['offer_id'] = (string) $request->offer_id;
            }
            if ($request->coupon_code) {
                $data['coupon_code'] = $request->coupon_code;
            }
            if ($request->expiry_date) {
                $data['expiry_date'] = $request->expiry_date;
            }

            $options = [];
            if ($request->image_url) {
                $options['image_url'] = $request->image_url;
            }

            // Check token counts
            $userIds = $request->has('user_ids') && !empty($request->user_ids) ? $request->user_ids : null;
            $totalTokens = $userIds 
                ? FcmToken::whereIn('user_id', $userIds)->count()
                : FcmToken::count();

            // Send to specific users or all
            if ($userIds) {
                $results = $this->fcmService->sendToUsers($userIds, $title, $body, $data);
            } else {
                $results = $this->fcmService->sendToAll($title, $body, $data);
            }

            Log::info('Promotional notification sent', [
                'admin_id' => $admin->id,
                'user_ids' => $userIds ?? 'all',
                'total_tokens' => $totalTokens,
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ]);

            $message = $results['sent'] > 0 
                ? 'Promotional notification sent'
                : ($totalTokens == 0 
                    ? ($userIds 
                        ? "No FCM tokens found for the specified users (IDs: " . implode(', ', $userIds) . "). Users need to register their FCM tokens first."
                        : "No FCM tokens found in the system. Users need to register their FCM tokens first.")
                    : "No notifications were sent. All tokens may be invalid or Firebase service issue.");

            return response()->json([
                'success' => true,
                'message' => $message,
                'sent_count' => $results['sent'],
                'failed_count' => $results['failed'],
                'total_tokens_found' => $totalTokens,
                'user_ids_requested' => $userIds ?? 'all'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send promotional notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send promotional notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send booking notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendBookingNotification(Request $request)
    {
        try {
            // Allow system/admin authentication
            $admin = $this->checkAdminAuth($request);
            $systemUser = null;
            
            // Also allow authenticated users for their own bookings
            if ($request->bearerToken()) {
                $systemUser = $request->user('sanctum');
            }

            if (!$admin && !$systemUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $request->validate([
                'booking_id' => 'required|string',
                'type' => 'required|in:confirmation,reminder,cancellation',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
                'passenger_phone' => 'nullable|string',
                'deep_link' => 'nullable|string',
                'data' => 'nullable|array',
            ]);

            $title = $request->title;
            $body = $request->message;
            $deepLink = $request->deep_link ?? 'Main/Bookings';
            
            $data = array_merge([
                'type' => 'booking',
                'notification_type' => 'booking',
                'deep_link' => $deepLink,
                'booking_id' => $request->booking_id,
                'booking_type' => $request->type,
            ], $request->data ?? []);

            $userId = $request->user_id;
            $passengerPhone = $request->passenger_phone;

            $sentToOwner = false;
            $sentToPassenger = false;

            // Send to booking owner
            $sentToOwner = $this->fcmService->sendToUser($userId, $title, $body, $data);

            // Send to passenger if different phone
            if ($passengerPhone) {
                // Find user by passenger phone
                $passenger = User::where('mobile', $passengerPhone)
                    ->orWhere('mobile', '91' . $passengerPhone)
                    ->orWhere('mobile', '+91' . $passengerPhone)
                    ->orWhereRaw('RIGHT(mobile, 10) = ?', [$passengerPhone])
                    ->first();

                if ($passenger && $passenger->id != $userId) {
                    $sentToPassenger = $this->fcmService->sendToUser($passenger->id, $title, $body, $data);
                } elseif ($passenger && $passenger->id == $userId) {
                    // Same user, already sent
                    $sentToPassenger = true;
                }
            }

            Log::info('Booking notification sent', [
                'booking_id' => $request->booking_id,
                'user_id' => $userId,
                'sent_to_owner' => $sentToOwner,
                'sent_to_passenger' => $sentToPassenger
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking notification sent',
                'sent_to_owner' => $sentToOwner,
                'sent_to_passenger' => $sentToPassenger
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send booking notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send booking notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send general notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendGeneralNotification(Request $request)
    {
        try {
            $admin = $this->checkAdminAuth($request);
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            $request->validate([
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'deep_link' => 'nullable|string',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'integer|exists:users,id',
                'priority' => 'nullable|in:high,normal,low',
            ]);

            $title = $request->title;
            $body = $request->message;
            $deepLink = $request->deep_link ?? 'Main/Home';
            $priority = $request->priority ?? 'high';

            $data = [
                'type' => 'general',
                'notification_type' => 'general',
                'deep_link' => $deepLink,
            ];

            // Check if user_ids provided and get token counts
            $userIds = $request->has('user_ids') && !empty($request->user_ids) ? $request->user_ids : null;
            $totalTokens = 0;
            
            if ($userIds) {
                // Count tokens for specified users
                $totalTokens = FcmToken::whereIn('user_id', $userIds)->count();
            } else {
                // Count all tokens
                $totalTokens = FcmToken::count();
            }

            // Send to specific users or all
            if ($userIds) {
                $results = $this->fcmService->sendToUsers($userIds, $title, $body, $data);
            } else {
                $results = $this->fcmService->sendToAll($title, $body, $data);
            }

            Log::info('General notification sent', [
                'admin_id' => $admin->id,
                'user_ids' => $userIds ?? 'all',
                'total_tokens' => $totalTokens,
                'priority' => $priority,
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ]);

            // Build response message
            $message = 'General notification sent';
            if ($results['sent'] == 0) {
                if ($totalTokens == 0) {
                    $message = 'No FCM tokens found. ' . ($userIds ? 'None of the specified users have registered FCM tokens.' : 'No users have registered FCM tokens in the system.');
                } else {
                    $message = 'Notification request processed but no notifications were sent. This may indicate all tokens are invalid or Firebase service issue.';
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'sent_count' => $results['sent'],
                'failed_count' => $results['failed'] ?? 0,
                'total_tokens_found' => $totalTokens,
                'user_ids_requested' => $userIds ?? 'all'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send general notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send general notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

