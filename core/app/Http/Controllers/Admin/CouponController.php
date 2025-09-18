<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Models\CouponTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    /**
     * Display the coupon management page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $pageTitle = 'Manage Coupons';
        $emptyMessage = 'No coupon found';
        $allCoupons = CouponTable::orderBy('created_at', 'desc')->get();

        // If an 'edit' ID is provided, pre-fill the form with that coupon's data
        $couponToEdit = null;
        if ($request->has('edit_id')) {
            $couponToEdit = CouponTable::find($request->edit_id);
        }

        return view('admin.trip.coupon', compact('allCoupons', 'couponToEdit', 'pageTitle', 'emptyMessage'));
    }

    /**
     * Store or update a coupon.
     *
     * @param StoreCouponRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreCouponRequest $request)
    {
        // Use a database transaction to ensure atomicity.
        // If coupon creation fails, other coupons won't be deactivated.
        DB::beginTransaction();
        try {
            // Deactivate all existing coupons first
            CouponTable::where('status', 1)->update(['status' => 0]);

            $validatedData = $request->validated();

            $couponData = [
                'coupon_name' => $validatedData['coupon_name'],
                'coupon_threshold' => $validatedData['coupon_threshold'],
                'discount_type' => $validatedData['discount_type'],
                'coupon_value' => $validatedData['coupon_value'],
                'expiry_date' => Carbon::parse($validatedData['expiry_date']),
                'status' => 1, // Set the newly created coupon as active
            ];

            // Handle image uploads
            $banner_path = $this->uploadImage($request, 'banner_image');
            Log::alert("message", [$banner_path])   ;
            $couponData['banner_image'] = $banner_path;
            $couponData['sticker_image'] = $this->uploadImage($request, 'sticker_image');

            CouponTable::create($couponData);

            DB::commit();

            // Clear the API cache after successful transaction
            Cache::forget('active_api_coupons');

            $notify[] = ['success', 'Coupon created and activated successfully.'];
            return redirect()->route('admin.coupon.index')->with('notify', $notify);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Coupon creation failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $notify[] = ['error', 'An unexpected error occurred. Could not create coupon.'];
            return back()->with('notify', $notify)->withInput();
        }
    }

    /**
     * Activate a specific coupon.
     *
     * @param integer $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate($id)
    {
        // Deactivate all other coupons
        CouponTable::where('id', '!=', $id)->update(['status' => 0]);

        // Activate the selected coupon
        $coupon = CouponTable::findOrFail($id);
        $coupon->status = 1;
        // If the coupon is expired, extend its expiry to today
        if ($coupon->expiry_date->isPast()) {
            $coupon->expiry_date = Carbon::today();
        }
        $coupon->save();

        // Clear the API cache
        Cache::forget('active_api_coupons');

        $notify[] = ['success', 'Coupon activated successfully.'];
        return back()->with('notify', $notify);
    }

    public function deactivate($id)
    {
        $coupon = CouponTable::findOrFail($id);
        $coupon->status = 0;
        $coupon->save();

        // Clear the API cache
        Cache::forget('active_api_coupons');

        $notify[] = ['success', 'Coupon deactivated successfully.'];
        return back()->with('notify', $notify);
    }

    public function delete($id)
    {
        $coupon = CouponTable::findOrFail($id);
        // Here you might want to add logic to remove images from storage
        $coupon->delete();

        // Clear the API cache
        Cache::forget('active_api_coupons');

        $notify[] = ['success', 'Coupon deleted successfully.'];
        return back()->with('notify', $notify);
    }

    /**
     * [API] Get all active coupons.
     * Implements caching to reduce database load.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveCouponsApi()
    {
        try {
            $coupons = Cache::remember('active_api_coupons', 600, function () { // Cache for 10 minutes
                return CouponTable::where('status', 1)
                    ->where('expiry_date', '>=', Carbon::today())
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($coupon) {
                        return [
                            'coupon_code' => $coupon->coupon_name,
                            'discount_type' => $coupon->discount_type,
                            'coupon_value' => (float) $coupon->coupon_value,
                            'coupon_threshold' => (float) $coupon->coupon_threshold,
                            'expiry_date' => $coupon->expiry_date->toDateString(),
                            'banner_image_url' => $coupon->banner_image ? getImage(imagePath()['coupon']['path'] . '/' . $coupon->banner_image) : null,
                            'sticker_image_url' => $coupon->sticker_image ? getImage(imagePath()['coupon']['path'] . '/' . $coupon->sticker_image) : null,
                        ];
                    });
            });

            if ($coupons->isEmpty()) {
                return response()->json(['success' => true, 'message' => 'No active coupons available.', 'data' => []]);
            }

            return response()->json(['success' => true, 'data' => $coupons]);
        } catch (\Exception $exp) {
            Log::error("Failed to fetch active coupons: " . $exp->getMessage(), ['trace' => $exp->getTraceAsString()]); 
            return response()->json(['success'=> false, 'message'=> $exp->getMessage()]);
        }

    }

    /**
     * Handle image upload for coupons.
     *
     * @param Request $request
     * @param string $fileInputName
     * @return string|null
     */
    private function uploadImage(Request $request, string $fileInputName): ?string
    {
        if (!$request->hasFile($fileInputName)) {
            return null;
        }

        try {
            $imagePaths = imagePath()['coupon'];
            $path = $imagePaths['path'];
            $size = $imagePaths['size'];
            return uploadImage($request->file($fileInputName), $path, $size);
        } catch (\Exception $e) {
            // Log the error and re-throw to be caught by the transaction rollback
            Log::error("Could not upload {$fileInputName}: " . $e->getMessage());
            throw new \Exception("Could not upload the {$fileInputName}.");
        }
    }

    /**
     * [API] Apply a coupon to a given price.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyCouponApi(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $coupon = CouponTable::where('coupon_name', $request->coupon_code)
            ->where('status', 1)
            ->where('expiry_date', '>=', Carbon::today())
            ->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired coupon code.'], 404);
        }

        $originalPrice = (float) $request->price;

        if ($originalPrice <= $coupon->coupon_threshold) {
            return response()->json(['success' => false, 'message' => 'This coupon is not applicable for the current cart value.'], 400);
        }

        $discountAmount = 0;
        if ($coupon->discount_type === 'fixed') {
            $discountAmount = (float) $coupon->coupon_value;
        } elseif ($coupon->discount_type === 'percentage') {
            $discountAmount = ($originalPrice * (float) $coupon->coupon_value) / 100;
        }

        $discountAmount = min($discountAmount, $originalPrice);
        $finalPrice = max(0, $originalPrice - $discountAmount);

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully.',
            'data' => [
                'original_price' => round($originalPrice, 2),
                'discount_amount' => round($discountAmount, 2),
                'final_price' => round($finalPrice, 2),
                'coupon_code' => $coupon->coupon_name,
            ]
        ]);
    }
}
