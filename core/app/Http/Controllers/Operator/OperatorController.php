<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('operator');
    }

    /**
     * Show the operator dashboard.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function dashboard()
    {
        $pageTitle = "Operator Dashboard";
        $operator = Auth::guard('operator')->user();

        // Get ALL TIME user bookings for this operator (not just last 30 days)
        // Exclude invalid dates (0000-00-00 or NULL)
        $userBookings = \App\Models\BookedTicket::where('operator_id', $operator->id)
            ->whereIn('booking_source', ['user', 'agent', 'admin'])
            ->confirmed() // Only confirmed/paid bookings (status 1)
            ->where('date_of_journey', '!=', '0000-00-00')
            ->whereNotNull('date_of_journey')
            ->get();

        // Calculate net revenue using the formula: Sum(unit_price - TDS - 5% of GST)
        // Where TDS = 5% of GST, so: Sum(unit_price - (gst * 0.10))
        $netRevenue = $userBookings->sum(function ($booking) {
            return $booking->net_revenue;
        });

        // Dashboard statistics
        $stats = [
            'total_routes' => $operator->routes()->count(),
            'active_routes' => $operator->activeRoutes()->count(),
            'total_buses' => $operator->buses()->count(),
            'active_buses' => $operator->activeBuses()->count(),
            'total_bookings' => $userBookings->count(),
            'total_revenue' => $netRevenue, // All time net revenue
        ];

        \Log::info('Operator Dashboard Stats - All Time', [
            'operator_id' => $operator->id,
            'total_user_bookings' => $userBookings->count(),
            'net_revenue_all_time' => $netRevenue,
            'stats' => $stats
        ]);

        return view('operator.dashboard', compact('pageTitle', 'operator', 'stats'));
    }

    /**
     * Show the operator profile.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function profile()
    {
        $pageTitle = "Profile";
        $operator = Auth::guard('operator')->user();

        return view('operator.profile', compact('pageTitle', 'operator'));
    }

    /**
     * Update the operator profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $operator = Auth::guard('operator')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
        ]);

        $operator->update($request->only([
            'name',
            'mobile',
            'address',
            'company_name',
            'city',
            'state'
        ]));

        $notify[] = ['success', 'Profile updated successfully.'];
        return back()->withNotify($notify);
    }

    /**
     * Show the change password form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function changePassword()
    {
        $pageTitle = "Change Password";
        return view('operator.change-password', compact('pageTitle'));
    }

    /**
     * Update the operator password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $operator = Auth::guard('operator')->user();

        if (!\Hash::check($request->current_password, $operator->password)) {
            $notify[] = ['error', 'Current password is incorrect.'];
            return back()->withNotify($notify);
        }

        $operator->update([
            'password' => bcrypt($request->password)
        ]);

        $notify[] = ['success', 'Password changed successfully.'];
        return back()->withNotify($notify);
    }
}
