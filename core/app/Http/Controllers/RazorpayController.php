<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use App\Models\MarkupTable;
use App\Models\BookedTicket;

class RazorpayController extends Controller
{
public function createOrder(Request $request)
{
    try {
        // Validate request
        $request->validate([
            'booking_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = $request->amount;
        $bookingId = $request->booking_id;

        $markup = MarkupTable::orderBy('id', 'desc')->first();
        $flatMarkup =  0;
        $percentageMarkup = 0;
        $threshold = $markup->threshold ?? 0;

        // Calculate final amount with markup
        if ($amount <= $threshold) {
            // Apply flat markup
            $finalAmount = $amount + $flatMarkup;
        } else {
            $finalAmount = $amount + ($amount * $percentageMarkup / 100);
        }

        // Convert final amount to integer paise for Razorpay
        $razorpayAmount = (int) round($finalAmount * 100); // This fixes the float-to-integer issue

        // Initialize Razorpay API
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        // Prepare order data
        $orderData = [
            'receipt' => $bookingId,
            'amount' => $razorpayAmount,
            'currency' => 'INR',
            'notes' => [
                'booking_id' => $bookingId,
                'actual_amount' => $amount,
                'final_amount' => $finalAmount,
            ],
        ];

        Log::info('Creating Razorpay order with actual amount', [
            'booking_id' => $bookingId,
            'amount' => $amount,
            'final_amount' => $finalAmount,
            'razorpay_amount' => $razorpayAmount
        ]);

        // Create the order
        $razorpayOrder = $api->order->create($orderData);

        Log::info('Razorpay order created successfully', [
            'order_id' => $razorpayOrder->id
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $razorpayOrder->id,
            'amount' => $finalAmount,
        ]);

    } catch (\Exception $e) {
        Log::error('Razorpay order creation failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to create payment order: ' . $e->getMessage(),
        ], 500);
    }
}

    
public function verifyPayment(Request $request)
{
    try {
        Log::info('Starting Razorpay payment verification', [
            'request_data' => $request->all()
        ]);

        // Validate request - all three parameters are required
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'booking_id' => 'required|string'
        ]);

        // Extract data
        $razorpayPaymentId = $request->razorpay_payment_id;
        $razorpayOrderId = $request->razorpay_order_id;
        $razorpaySignature = $request->razorpay_signature;
        $bookingId = $request->booking_id;

        Log::info('Extracted Razorpay payment data', [
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_signature' => $razorpaySignature,
            'booking_id' => $bookingId
        ]);

        // Initialize Razorpay API
        $razorpayKey = env('RAZORPAY_KEY');
        $razorpaySecret = env('RAZORPAY_SECRET');

        if (empty($razorpaySecret)) {
            Log::error('Razorpay secret is missing in env');
            throw new \Exception('Razorpay secret is missing');
        }

        Log::info('Initializing Razorpay API with secret check', [
            'razorpay_key' => $razorpayKey,
            'razorpay_secret_present' => !empty($razorpaySecret)
        ]);

        $api = new Api($razorpayKey, $razorpaySecret);

        // Verify signature
        $attributes = [
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_signature' => $razorpaySignature
        ];

        Log::info('Verifying Razorpay signature with attributes', $attributes);

        $api->utility->verifyPaymentSignature($attributes);

        Log::info('✅ Razorpay signature verification successful for booking_id: ' . $bookingId);

        // Call the bookTicketApi method to finalize the booking
        $bookingController = new SiteController();
        $bookingResult = $bookingController->bookTicketApi(new Request([
            'booking_id' => $bookingId,
            'payment_id' => $razorpayPaymentId,
            'payment_status' => 'success'
        ]));

        // Return JSON with redirect to print ticket page
        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully',
            'redirect' => route('user.print.ticket', $bookingId)
        ]);
    } catch (\Exception $e) {
        Log::error('❌ Razorpay payment verification failed', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'razorpay_payment_id' => $request->razorpay_payment_id ?? null,
            'razorpay_order_id' => $request->razorpay_order_id ?? null,
            'razorpay_signature' => $request->razorpay_signature ?? null,
            'booking_id' => $request->booking_id ?? null
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Payment verification failed: ' . $e->getMessage()
        ], 500);
    }
}
}