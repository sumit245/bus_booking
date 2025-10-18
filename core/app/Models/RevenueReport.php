<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RevenueReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'report_date',
        'total_tickets',
        'total_revenue',
        'user_bookings_revenue',
        'operator_bookings_revenue',
        'unit_price_total',
        'sub_total_total',
        'agent_commission_total',
        'platform_commission',
        'payment_gateway_fees',
        'tds_amount',
        'net_payable',
        'detailed_breakdown',
        'report_type',
        'period_start',
        'period_end'
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'total_tickets' => 'decimal:0',
        'total_revenue' => 'decimal:2',
        'user_bookings_revenue' => 'decimal:2',
        'operator_bookings_revenue' => 'decimal:2',
        'unit_price_total' => 'decimal:2',
        'sub_total_total' => 'decimal:2',
        'agent_commission_total' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'payment_gateway_fees' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'detailed_breakdown' => 'array'
    ];

    // Relationships
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    // Scopes
    public function scopeForOperator($query, $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('report_date', $date);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('report_date', '>=', $startDate)
            ->where('report_date', '<=', $endDate);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    // Accessors
    public function getFormattedTotalRevenueAttribute()
    {
        return '₹' . number_format($this->total_revenue, 2);
    }

    public function getFormattedNetPayableAttribute()
    {
        return '₹' . number_format($this->net_payable, 2);
    }

    public function getFormattedPlatformCommissionAttribute()
    {
        return '₹' . number_format($this->platform_commission, 2);
    }

    // Methods
    public static function generateDailyReport($operatorId, $date)
    {
        $reportDate = Carbon::parse($date)->toDateString();

        // Check if report already exists
        $existingReport = static::forOperator($operatorId)
            ->forDate($reportDate)
            ->byType('daily')
            ->first();

        if ($existingReport) {
            return $existingReport;
        }

        // Get all tickets for the date
        $userBookings = BookedTicket::where('operator_id', $operatorId)
            ->whereDate('date_of_journey', $reportDate)
            ->whereIn('status', [0, 1, 2])
            ->get();

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

        // Calculate totals
        $userBookingsRevenue = $userBookings->sum('total_amount');
        $operatorBookingsRevenue = $operatorBookings->sum('blocked_amount');
        $totalRevenue = $userBookingsRevenue + $operatorBookingsRevenue;

        $totalTickets = $userBookings->count() + $operatorBookings->count();
        $unitPriceTotal = $userBookings->sum('unit_price');
        $subTotalTotal = $userBookings->sum('sub_total');
        $agentCommissionTotal = $userBookings->sum('agent_commission');

        // Calculate platform fees (5% of total revenue)
        $platformCommission = $totalRevenue * 0.05;

        // Calculate payment gateway fees (2% of user bookings)
        $paymentGatewayFees = $userBookingsRevenue * 0.02;

        // Calculate TDS (10% of net amount)
        $netBeforeTds = $totalRevenue - $platformCommission - $paymentGatewayFees;
        $tdsAmount = $netBeforeTds * 0.10;

        $netPayable = $netBeforeTds - $tdsAmount;

        // Detailed breakdown
        $detailedBreakdown = [
            'user_bookings' => [
                'count' => $userBookings->count(),
                'revenue' => $userBookingsRevenue,
                'breakdown' => $userBookings->groupBy('booking_type')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'revenue' => $group->sum('total_amount')
                    ];
                })
            ],
            'operator_bookings' => [
                'count' => $operatorBookings->count(),
                'revenue' => $operatorBookingsRevenue,
                'breakdown' => $operatorBookings->groupBy('booking_reason')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'revenue' => $group->sum('blocked_amount')
                    ];
                })
            ],
            'fees' => [
                'platform_commission' => $platformCommission,
                'payment_gateway_fees' => $paymentGatewayFees,
                'tds_amount' => $tdsAmount
            ]
        ];

        return static::create([
            'operator_id' => $operatorId,
            'report_date' => $reportDate,
            'total_tickets' => $totalTickets,
            'total_revenue' => $totalRevenue,
            'user_bookings_revenue' => $userBookingsRevenue,
            'operator_bookings_revenue' => $operatorBookingsRevenue,
            'unit_price_total' => $unitPriceTotal,
            'sub_total_total' => $subTotalTotal,
            'agent_commission_total' => $agentCommissionTotal,
            'platform_commission' => $platformCommission,
            'payment_gateway_fees' => $paymentGatewayFees,
            'tds_amount' => $tdsAmount,
            'net_payable' => $netPayable,
            'detailed_breakdown' => $detailedBreakdown,
            'report_type' => 'daily'
        ]);
    }

    public static function generatePeriodReport($operatorId, $startDate, $endDate, $type = 'custom')
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        // Check if report already exists
        $existingReport = static::forOperator($operatorId)
            ->where('period_start', $start)
            ->where('period_end', $end)
            ->byType($type)
            ->first();

        if ($existingReport) {
            return $existingReport;
        }

        // Get all daily reports for the period
        $dailyReports = static::forOperator($operatorId)
            ->forPeriod($start, $end)
            ->byType('daily')
            ->get();

        if ($dailyReports->isEmpty()) {
            // Generate daily reports for the period if they don't exist
            $currentDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);

            while ($currentDate <= $endDate) {
                static::generateDailyReport($operatorId, $currentDate->toDateString());
                $currentDate->addDay();
            }

            // Get the generated reports
            $dailyReports = static::forOperator($operatorId)
                ->forPeriod($start, $end)
                ->byType('daily')
                ->get();
        }

        // Aggregate data
        $totalTickets = $dailyReports->sum('total_tickets');
        $totalRevenue = $dailyReports->sum('total_revenue');
        $userBookingsRevenue = $dailyReports->sum('user_bookings_revenue');
        $operatorBookingsRevenue = $dailyReports->sum('operator_bookings_revenue');
        $unitPriceTotal = $dailyReports->sum('unit_price_total');
        $subTotalTotal = $dailyReports->sum('sub_total_total');
        $agentCommissionTotal = $dailyReports->sum('agent_commission_total');
        $platformCommission = $dailyReports->sum('platform_commission');
        $paymentGatewayFees = $dailyReports->sum('payment_gateway_fees');
        $tdsAmount = $dailyReports->sum('tds_amount');
        $netPayable = $dailyReports->sum('net_payable');

        return static::create([
            'operator_id' => $operatorId,
            'report_date' => $start,
            'total_tickets' => $totalTickets,
            'total_revenue' => $totalRevenue,
            'user_bookings_revenue' => $userBookingsRevenue,
            'operator_bookings_revenue' => $operatorBookingsRevenue,
            'unit_price_total' => $unitPriceTotal,
            'sub_total_total' => $subTotalTotal,
            'agent_commission_total' => $agentCommissionTotal,
            'platform_commission' => $platformCommission,
            'payment_gateway_fees' => $paymentGatewayFees,
            'tds_amount' => $tdsAmount,
            'net_payable' => $netPayable,
            'detailed_breakdown' => $dailyReports->pluck('detailed_breakdown')->toArray(),
            'report_type' => $type,
            'period_start' => $start,
            'period_end' => $end
        ]);
    }
}
