<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

class RazorpayController extends Controller
{
    public function createOrder(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'booking_id' => 'required|string'
            ]);

            $amount = $request->amount;
            $bookingId = $request->booking_id;
            
            // Initialize Razorpay API
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            
            // Create order
            $orderData = [
                'receipt' => $bookingId,
                'amount' => $amount * 100, // Convert to paise
                'currency' => 'INR',
                'notes' => [
                    'booking_id' => $bookingId
                ]
            ];
            
            $razorpayOrder = $api->order->create($orderData);
            
            return response()->json([
                'success' => true,
                'order_id' => $razorpayOrder->id,
                'amount' => $amount
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function verifyPayment(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'razorpay_payment_id' => 'required|string',
                'razorpay_order_id' => 'required|string',
                'razorpay_signature' => 'required|string',
                'booking_id' => 'required|string'
            ]);
            
            // Get payment data
            $razorpayPaymentId = $request->razorpay_payment_id;
            $razorpayOrderId = $request->razorpay_order_id;
            $razorpaySignature = $request->razorpay_signature;
            $bookingId = $request->booking_id;
            
            // Initialize Razorpay API
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            
            // Verify signature
            $attributes = [
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_signature' => $razorpaySignature
            ];
            
            $api->utility->verifyPaymentSignature($attributes);
            
            // If we reach here, signature is valid
            // Process the booking confirmation logic here
            
            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'redirect' => route('ticket.history')
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay payment verification failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
