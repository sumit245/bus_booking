<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BookedTicket;
use App\Models\AgentBooking;
use App\Services\AgentCommissionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $commissionCalculator;

    public function __construct(AgentCommissionCalculator $commissionCalculator)
    {
        $this->commissionCalculator = $commissionCalculator;
    }

    /**
     * Show agent dashboard
     */
    public function index()
    {
        $agent = Auth::guard('agent')->user();

        // Get dashboard statistics
        $stats = $this->getDashboardStats($agent);

        // Get recent bookings
        $recentBookings = $this->getRecentBookings($agent);

        // Get monthly earnings
        $monthlyEarnings = $this->getMonthlyEarnings($agent);

        // Get commission configuration
        $commissionConfig = $this->commissionCalculator->getCommissionConfig();

        return view('agent.dashboard.index', compact(
            'agent',
            'stats',
            'recentBookings',
            'monthlyEarnings',
            'commissionConfig'
        ));
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Agent $agent)
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        // Eager load agentBookings with bookedTicket to avoid N+1 queries
        $agentBookings = $agent->agentBookings()->with('bookedTicket')->get();

        return [
            'total_bookings' => $agentBookings->count(),
            'today_bookings' => $agentBookings->filter(function ($booking) use ($today) {
                return $booking->bookedTicket && $booking->bookedTicket->created_at->startOfDay()->equalTo($today);
            })->count(),
            'monthly_bookings' => $agentBookings->filter(function ($booking) use ($thisMonth) {
                return $booking->bookedTicket && $booking->bookedTicket->created_at->startOfMonth()->greaterThanOrEqualTo($thisMonth);
            })->count(),
            'total_earnings' => $agentBookings->sum('total_commission_earned'),
            'monthly_earnings' => $agentBookings->filter(function ($booking) use ($thisMonth) {
                return $booking->bookedTicket && $booking->bookedTicket->created_at->startOfMonth()->greaterThanOrEqualTo($thisMonth);
            })->sum('total_commission_earned'),
            'pending_bookings' => $agentBookings->where('booking_status', 'pending')->count(),
            'confirmed_bookings' => $agentBookings->where('booking_status', 'confirmed')->count(),
        ];
    }

    /**
     * Get recent bookings
     */
    private function getRecentBookings(Agent $agent)
    {
        return $agent->agentBookings()
            ->with(['bookedTicket.trip.startFrom', 'bookedTicket.trip.endTo'])
            ->latest()
            ->take(5)
            ->get();
    }

    /**
     * Get monthly earnings breakdown
     */
    private function getMonthlyEarnings(Agent $agent)
    {
        $thisMonth = now()->startOfMonth();

        return $agent->agentBookings()
            ->with('bookedTicket')
            ->whereHas('bookedTicket', function ($query) use ($thisMonth) {
                $query->whereDate('created_at', '>=', $thisMonth);
            })
            ->selectRaw('DATE(created_at) as date, SUM(total_commission_earned) as earnings')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('earnings', 'date');
    }

    /**
     * Get dashboard data for API
     */
    public function getDashboardData()
    {
        $agent = Auth::guard('agent')->user();
        $stats = $this->getDashboardStats($agent);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get recent activity for API
     */
    public function getRecentActivity()
    {
        $agent = Auth::guard('agent')->user();
        $recentBookings = $this->getRecentBookings($agent);

        return response()->json([
            'success' => true,
            'data' => $recentBookings
        ]);
    }
}
