<?php

namespace App\Services;

use App\Models\BookedTicket;
use App\Models\OperatorBooking;
use App\Models\RevenueReport;
use App\Models\OperatorPayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RevenueCalculator
{
    /**
     * Calculate revenue for a specific operator and date
     */
    public function calculateDailyRevenue(int $operatorId, string $date): array
    {
        $reportDate = Carbon::parse($date)->toDateString();

        // Get user bookings
        $userBookings = BookedTicket::where('operator_id', $operatorId)
            ->whereDate('date_of_journey', $reportDate)
            ->whereIn('status', [0, 1, 2])
            ->get();

        // Get operator bookings
        $operatorBookings = OperatorBooking::where('operator_id', $operatorId)
            ->where(function ($query) use ($reportDate) {
                $query->whereDate('journey_date', $reportDate)
                    ->orWhere(function ($q) use ($reportDate) {
                        $q->where('is_date_range', true)
                            ->whereDate('journey_date', '<=', $reportDate)
                            ->whereDate('journey_date_end', '>=', $reportDate);
                    });
            })
            ->where('status', 'active')
            ->get();

        return $this->processRevenueData($userBookings, $operatorBookings, $reportDate);
    }

    /**
     * Calculate revenue for a date range
     */
    public function calculatePeriodRevenue(int $operatorId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        // Get user bookings
        $userBookings = BookedTicket::where('operator_id', $operatorId)
            ->whereBetween('date_of_journey', [$start, $end])
            ->whereIn('status', [0, 1, 2])
            ->get();

        // Get operator bookings
        $operatorBookings = OperatorBooking::where('operator_id', $operatorId)
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    $q->whereDate('journey_date', '>=', $start)
                        ->whereDate('journey_date', '<=', $end);
                })
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('is_date_range', true)
                            ->where(function ($q2) use ($start, $end) {
                                $q2->where(function ($q3) use ($start, $end) {
                                    $q3->whereDate('journey_date', '<=', $end)
                                        ->whereDate('journey_date_end', '>=', $start);
                                });
                            });
                    });
            })
            ->where('status', 'active')
            ->get();

        return $this->processRevenueData($userBookings, $operatorBookings, $start, $end);
    }

    /**
     * Process revenue data and calculate all metrics
     */
    private function processRevenueData($userBookings, $operatorBookings, $startDate, $endDate = null): array
    {
        // Basic revenue calculations
        $userBookingsRevenue = $userBookings->sum('total_amount');
        $operatorBookingsRevenue = $operatorBookings->sum('blocked_amount');
        $totalRevenue = $userBookingsRevenue + $operatorBookingsRevenue;

        $totalTickets = $userBookings->count() + $operatorBookings->count();
        $unitPriceTotal = $userBookings->sum('unit_price');
        $subTotalTotal = $userBookings->sum('sub_total');
        $agentCommissionTotal = $userBookings->sum('agent_commission');

        // Fee calculations
        $fees = $this->calculateFees($totalRevenue, $userBookingsRevenue);

        // Detailed breakdown
        $breakdown = $this->generateDetailedBreakdown($userBookings, $operatorBookings, $fees);

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate ?: $startDate,
                'type' => $endDate ? 'range' : 'single'
            ],
            'summary' => [
                'total_tickets' => $totalTickets,
                'total_revenue' => $totalRevenue,
                'user_bookings_revenue' => $userBookingsRevenue,
                'operator_bookings_revenue' => $operatorBookingsRevenue,
                'unit_price_total' => $unitPriceTotal,
                'sub_total_total' => $subTotalTotal,
                'agent_commission_total' => $agentCommissionTotal,
            ],
            'fees' => $fees,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Calculate all fees and deductions
     */
    private function calculateFees(float $totalRevenue, float $userBookingsRevenue): array
    {
        // Platform commission (5% of total revenue)
        $platformCommission = $totalRevenue * 0.05;

        // Payment gateway fees (2% of user bookings only)
        $paymentGatewayFees = $userBookingsRevenue * 0.02;

        // Calculate net before TDS
        $netBeforeTds = $totalRevenue - $platformCommission - $paymentGatewayFees;

        // TDS (10% of net amount)
        $tdsAmount = $netBeforeTds * 0.10;

        // Final net payable
        $netPayable = $netBeforeTds - $tdsAmount;

        return [
            'platform_commission' => $platformCommission,
            'payment_gateway_fees' => $paymentGatewayFees,
            'tds_amount' => $tdsAmount,
            'total_deductions' => $platformCommission + $paymentGatewayFees + $tdsAmount,
            'net_payable' => $netPayable
        ];
    }

    /**
     * Generate detailed breakdown of revenue sources
     */
    private function generateDetailedBreakdown($userBookings, $operatorBookings, $fees): array
    {
        // User bookings breakdown by booking type
        $userBookingsBreakdown = $userBookings->groupBy('booking_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'revenue' => $group->sum('total_amount'),
                'unit_price' => $group->sum('unit_price'),
                'sub_total' => $group->sum('sub_total'),
                'agent_commission' => $group->sum('agent_commission'),
                'avg_ticket_value' => $group->count() > 0 ? $group->sum('total_amount') / $group->count() : 0
            ];
        });

        // Operator bookings breakdown by reason
        $operatorBookingsBreakdown = $operatorBookings->groupBy('booking_reason')->map(function ($group) {
            return [
                'count' => $group->count(),
                'revenue' => $group->sum('blocked_amount'),
                'avg_booking_value' => $group->count() > 0 ? $group->sum('blocked_amount') / $group->count() : 0
            ];
        });

        // Top performing routes/buses
        $topRoutes = $userBookings->groupBy('route_id')->map(function ($group) {
            return [
                'route_id' => $group->first()->route_id,
                'count' => $group->count(),
                'revenue' => $group->sum('total_amount')
            ];
        })->sortByDesc('revenue')->take(5);

        $topBuses = $userBookings->groupBy('bus_id')->map(function ($group) {
            return [
                'bus_id' => $group->first()->bus_id,
                'count' => $group->count(),
                'revenue' => $group->sum('total_amount')
            ];
        })->sortByDesc('revenue')->take(5);

        return [
            'user_bookings' => [
                'total' => [
                    'count' => $userBookings->count(),
                    'revenue' => $userBookings->sum('total_amount'),
                    'avg_ticket_value' => $userBookings->count() > 0 ? $userBookings->sum('total_amount') / $userBookings->count() : 0
                ],
                'by_booking_type' => $userBookingsBreakdown,
                'top_routes' => $topRoutes,
                'top_buses' => $topBuses
            ],
            'operator_bookings' => [
                'total' => [
                    'count' => $operatorBookings->count(),
                    'revenue' => $operatorBookings->sum('blocked_amount'),
                    'avg_booking_value' => $operatorBookings->count() > 0 ? $operatorBookings->sum('blocked_amount') / $operatorBookings->count() : 0
                ],
                'by_reason' => $operatorBookingsBreakdown
            ],
            'fees_breakdown' => [
                'platform_commission' => [
                    'amount' => $fees['platform_commission'],
                    'percentage' => $userBookings->sum('total_amount') + $operatorBookings->sum('blocked_amount') > 0
                        ? ($fees['platform_commission'] / ($userBookings->sum('total_amount') + $operatorBookings->sum('blocked_amount'))) * 100
                        : 0
                ],
                'payment_gateway_fees' => [
                    'amount' => $fees['payment_gateway_fees'],
                    'percentage' => $userBookings->sum('total_amount') > 0
                        ? ($fees['payment_gateway_fees'] / $userBookings->sum('total_amount')) * 100
                        : 0
                ],
                'tds_amount' => [
                    'amount' => $fees['tds_amount'],
                    'percentage' => ($userBookings->sum('total_amount') + $operatorBookings->sum('blocked_amount') - $fees['platform_commission'] - $fees['payment_gateway_fees']) > 0
                        ? ($fees['tds_amount'] / ($userBookings->sum('total_amount') + $operatorBookings->sum('blocked_amount') - $fees['platform_commission'] - $fees['payment_gateway_fees'])) * 100
                        : 0
                ]
            ]
        ];
    }

    /**
     * Generate and save revenue report
     */
    public function generateReport(int $operatorId, string $date, string $type = 'daily'): RevenueReport
    {
        if ($type === 'daily') {
            return RevenueReport::generateDailyReport($operatorId, $date);
        } else {
            $endDate = Carbon::parse($date)->addDays(6)->toDateString(); // For weekly
            return RevenueReport::generatePeriodReport($operatorId, $date, $endDate, $type);
        }
    }

    /**
     * Generate payout for operator
     */
    public function generatePayout(int $operatorId, string $startDate, string $endDate): OperatorPayout
    {
        return OperatorPayout::generatePayoutForPeriod($operatorId, $startDate, $endDate);
    }

    /**
     * Get revenue summary for dashboard
     */
    public function getRevenueSummary(int $operatorId, int $days = 30): array
    {
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::now()->subDays($days)->toDateString();

        $revenueData = $this->calculatePeriodRevenue($operatorId, $startDate, $endDate);

        // Get recent payouts
        $recentPayouts = OperatorPayout::forOperator($operatorId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Calculate pending amount
        $pendingPayouts = OperatorPayout::forOperator($operatorId)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->get();

        $totalPending = $pendingPayouts->sum('pending_amount');

        return [
            'summary' => $revenueData['summary'],
            'fees' => $revenueData['fees'],
            'recent_payouts' => $recentPayouts,
            'pending_amount' => $totalPending,
            'period' => $revenueData['period']
        ];
    }

    /**
     * Export revenue data for external use
     */
    public function exportRevenueData(int $operatorId, string $startDate, string $endDate, string $format = 'array'): mixed
    {
        $revenueData = $this->calculatePeriodRevenue($operatorId, $startDate, $endDate);

        if ($format === 'csv') {
            return $this->convertToCsv($revenueData);
        } elseif ($format === 'json') {
            return json_encode($revenueData, JSON_PRETTY_PRINT);
        }

        return $revenueData;
    }

    /**
     * Convert revenue data to CSV format
     */
    private function convertToCsv(array $data): string
    {
        $csv = "Revenue Report\n";
        $csv .= "Period: {$data['period']['start']} to {$data['period']['end']}\n\n";

        $csv .= "Summary\n";
        $csv .= "Total Tickets," . $data['summary']['total_tickets'] . "\n";
        $csv .= "Total Revenue,₹" . number_format($data['summary']['total_revenue'], 2) . "\n";
        $csv .= "User Bookings Revenue,₹" . number_format($data['summary']['user_bookings_revenue'], 2) . "\n";
        $csv .= "Operator Bookings Revenue,₹" . number_format($data['summary']['operator_bookings_revenue'], 2) . "\n";
        $csv .= "Agent Commission,₹" . number_format($data['summary']['agent_commission_total'], 2) . "\n\n";

        $csv .= "Fees & Deductions\n";
        $csv .= "Platform Commission,₹" . number_format($data['fees']['platform_commission'], 2) . "\n";
        $csv .= "Payment Gateway Fees,₹" . number_format($data['fees']['payment_gateway_fees'], 2) . "\n";
        $csv .= "TDS Amount,₹" . number_format($data['fees']['tds_amount'], 2) . "\n";
        $csv .= "Net Payable,₹" . number_format($data['fees']['net_payable'], 2) . "\n";

        return $csv;
    }
}
