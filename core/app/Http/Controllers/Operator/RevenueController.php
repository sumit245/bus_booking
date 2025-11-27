<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RevenueReport;
use App\Models\OperatorPayout;
use App\Services\RevenueCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

class RevenueController extends Controller
{
    protected $revenueCalculator;

    public function __construct(RevenueCalculator $revenueCalculator)
    {
        $this->revenueCalculator = $revenueCalculator;
    }

    /**
     * Show revenue dashboard
     */
    public function dashboard(Request $request)
    {
        $pageTitle = 'Revenue Dashboard';
        $operator = auth('operator')->user();

        try {
            // Determine date range based on period filter
            $period = $request->get('period', 'last30');

            switch ($period) {
                case 'all':
                    // Get the earliest booking date
                    $firstBooking = \App\Models\BookedTicket::where('operator_id', $operator->id)
                        ->where('status', 1)
                        ->orderBy('date_of_journey', 'asc')
                        ->first();

                    if ($firstBooking) {
                        // date_of_journey might be a string or Carbon instance
                        $startDate = $firstBooking->date_of_journey instanceof \Carbon\Carbon
                            ? $firstBooking->date_of_journey->toDateString()
                            : $firstBooking->date_of_journey;
                    } else {
                        $startDate = Carbon::now()->subYear()->toDateString();
                    }

                    $endDate = Carbon::now()->toDateString();
                    $days = Carbon::parse($startDate)->diffInDays($endDate);
                    break;

                case 'today':
                    $startDate = Carbon::now()->toDateString();
                    $endDate = Carbon::now()->toDateString();
                    $days = 1;
                    break;

                case 'last7':
                    $startDate = Carbon::now()->subDays(7)->toDateString();
                    $endDate = Carbon::now()->toDateString();
                    $days = 7;
                    break;

                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth()->toDateString();
                    $endDate = Carbon::now()->toDateString();
                    $days = Carbon::now()->day;
                    break;

                case 'last_month':
                    $startDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                    $endDate = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                    $days = Carbon::parse($startDate)->diffInDays($endDate) + 1;
                    break;

                case 'custom':
                    $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
                    $endDate = $request->get('end_date', Carbon::now()->toDateString());
                    $days = Carbon::parse($startDate)->diffInDays($endDate) + 1;
                    break;

                case 'last30':
                default:
                    $startDate = Carbon::now()->subDays(30)->toDateString();
                    $endDate = Carbon::now()->toDateString();
                    $days = 30;
                    break;
            }

            // Get revenue summary for the selected period
            if ($period == 'all') {
                // For all time, calculate directly without using getRevenueSummary
                $revenueSummary = $this->revenueCalculator->calculatePeriodRevenue($operator->id, $startDate, $endDate);
            } else {
                $revenueSummary = $this->revenueCalculator->calculatePeriodRevenue($operator->id, $startDate, $endDate);
            }

            // Calculate pending amount from payouts
            $pendingPayouts = OperatorPayout::forOperator($operator->id)
                ->whereIn('payment_status', ['pending', 'partial'])
                ->get();

            $revenueSummary['pending_amount'] = $pendingPayouts->sum('pending_amount');

            // Get recent revenue reports
            $recentReports = RevenueReport::forOperator($operator->id)
                ->orderBy('report_date', 'desc')
                ->take(10)
                ->get();

            // Get pending payouts
            $pendingPayouts = OperatorPayout::forOperator($operator->id)
                ->pending()
                ->orderBy('created_at', 'desc')
                ->get();

            // Get recent payouts
            $recentPayouts = OperatorPayout::forOperator($operator->id)
                ->whereIn('payment_status', ['paid', 'partial'])
                ->orderBy('paid_date', 'desc')
                ->take(10)
                ->get();

            return view('operator.revenue.dashboard', compact(
                'pageTitle',
                'revenueSummary',
                'recentReports',
                'pendingPayouts',
                'recentPayouts'
            ));

        } catch (\Exception $e) {
            Log::error('Revenue dashboard error: ' . $e->getMessage(), [
                'operator_id' => $operator->id,
                'exception' => $e
            ]);

            return redirect()->back()->with('error', 'Unable to load revenue dashboard. Please try again.');
        }
    }

    /**
     * Show revenue reports
     */
    public function reports(Request $request)
    {
        $operator = auth('operator')->user();

        $query = RevenueReport::forOperator($operator->id);

        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('report_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('report_date', '<=', $request->end_date);
        }

        if ($request->filled('report_type')) {
            $query->byType($request->report_type);
        }

        // Apply sorting
        $sortColumn = $request->get('sort', 'report_date');
        $sortOrder = $request->get('order', 'desc');

        // Validate sort column to prevent SQL injection
        $allowedSorts = [
            'report_date',
            'report_type',
            'total_tickets',
            'total_revenue',
            'user_bookings_revenue',
            'operator_bookings_revenue',
            'platform_commission',
            'net_payable'
        ];

        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortOrder);
        } else {
            $query->orderBy('report_date', 'desc');
        }

        // Get per_page from request or default to 20
        $perPage = $request->get('per_page', 20);

        // Validate per_page value
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 20;
        }

        $reports = $query->paginate($perPage);

        return view('operator.revenue.reports', compact('reports'));
    }

    /**
     * Show detailed revenue report
     */
    public function showReport($reportId)
    {
        $operator = auth('operator')->user();

        $report = RevenueReport::forOperator($operator->id)
            ->findOrFail($reportId);

        return view('operator.revenue.report-detail', compact('report'));
    }

    /**
     * Generate new revenue report
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:daily,weekly'
        ]);

        $operator = auth('operator')->user();

        try {
            $report = $this->revenueCalculator->generateReport(
                $operator->id,
                $request->date,
                $request->type
            );

            return redirect()->route('operator.revenue.reports.show', $report->id)
                ->with('success', 'Revenue report generated successfully.');

        } catch (\Exception $e) {
            Log::error('Generate revenue report error: ' . $e->getMessage(), [
                'operator_id' => $operator->id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to generate revenue report. Please try again.');
        }
    }

    /**
     * Show payout history
     */
    public function payouts(Request $request)
    {
        $operator = auth('operator')->user();

        $query = OperatorPayout::forOperator($operator->id);

        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('payout_period_start', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('payout_period_end', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('operator.revenue.payouts', compact('payouts'));
    }

    /**
     * Show detailed payout
     */
    public function showPayout($payoutId)
    {
        $operator = auth('operator')->user();

        $payout = OperatorPayout::forOperator($operator->id)
            ->findOrFail($payoutId);

        return view('operator.revenue.payout-detail', compact('payout'));
    }

    /**
     * Export revenue reports to Excel/CSV
     */
    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'formatExport' => 'required|in:csv,json'
        ]);

        $operator = auth('operator')->user();

        try {
            $query = RevenueReport::forOperator($operator->id);

            // Apply filters
            $query->where('report_date', '>=', $request->start_date)
                ->where('report_date', '<=', $request->end_date);

            if ($request->filled('report_type')) {
                $query->byType($request->report_type);
            }

            // Apply sorting
            $sortColumn = $request->get('sort', 'report_date');
            $sortOrder = $request->get('order', 'desc');

            $allowedSorts = [
                'report_date',
                'report_type',
                'total_tickets',
                'total_revenue',
                'user_bookings_revenue',
                'operator_bookings_revenue',
                'platform_commission',
                'net_payable'
            ];

            if (in_array($sortColumn, $allowedSorts)) {
                $query->orderBy($sortColumn, $sortOrder);
            } else {
                $query->orderBy('report_date', 'desc');
            }

            $reports = $query->get();

            if ($request->formatExport === 'csv') {
                $filename = "revenue_reports_{$operator->id}_{$request->start_date}_to_{$request->end_date}.csv";

                // Create CSV using League\Csv for better Excel compatibility
                $csv = Writer::createFromString('');

                // Set UTF-8 BOM for Excel compatibility
                $csv->setOutputBOM(Writer::BOM_UTF8);

                // Add CSV headers
                $csv->insertOne([
                    'Date',
                    'Type',
                    'Total Tickets',
                    'Total Revenue (₹)',
                    'User Bookings Revenue (₹)',
                    'Operator Bookings Revenue (₹)',
                    'Platform Commission (₹)',
                    'Payment Gateway Fees (₹)',
                    'TDS Amount (₹)',
                    'Net Payable (₹)'
                ]);

                // Add data rows
                foreach ($reports as $report) {
                    $csv->insertOne([
                        $report->report_date->format('Y-m-d'),
                        ucfirst($report->report_type),
                        $report->total_tickets,
                        number_format($report->total_revenue, 2, '.', ''),
                        number_format($report->user_bookings_revenue, 2, '.', ''),
                        number_format($report->operator_bookings_revenue, 2, '.', ''),
                        number_format($report->platform_commission, 2, '.', ''),
                        number_format($report->payment_gateway_fees, 2, '.', ''),
                        number_format($report->tds_amount, 2, '.', ''),
                        number_format($report->net_payable, 2, '.', '')
                    ]);
                }

                // Output the CSV
                return response($csv->toString(), 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    'Pragma' => 'no-cache',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Expires' => '0'
                ]);

            } else {
                // JSON export
                $filename = "revenue_reports_{$operator->id}_{$request->start_date}_to_{$request->end_date}.json";

                $data = $reports->map(function ($report) {
                    return [
                        'date' => $report->report_date->format('Y-m-d'),
                        'type' => $report->report_type,
                        'total_tickets' => $report->total_tickets,
                        'total_revenue' => $report->total_revenue,
                        'user_bookings_revenue' => $report->user_bookings_revenue,
                        'operator_bookings_revenue' => $report->operator_bookings_revenue,
                        'platform_commission' => $report->platform_commission,
                        'payment_gateway_fees' => $report->payment_gateway_fees,
                        'tds_amount' => $report->tds_amount,
                        'net_payable' => $report->net_payable
                    ];
                });

                return response()->json($data)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
            }

        } catch (\Exception $e) {
            Log::error('Export revenue reports error: ' . $e->getMessage(), [
                'operator_id' => $operator->id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to export revenue reports. Please try again.');
        }
    }

    /**
     * Get revenue chart data for AJAX
     */
    public function chartData(Request $request)
    {
        $operator = auth('operator')->user();

        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days)->toDateString();
        $endDate = Carbon::now()->toDateString();

        try {
            $reports = RevenueReport::forOperator($operator->id)
                ->forPeriod($startDate, $endDate)
                ->byType('daily')
                ->orderBy('report_date')
                ->get();

            $chartData = [
                'labels' => $reports->pluck('report_date')->map(function ($date) {
                    return Carbon::parse($date)->format('M d');
                }),
                'datasets' => [
                    [
                        'label' => 'Total Revenue',
                        'data' => $reports->pluck('total_revenue'),
                        'borderColor' => '#007bff',
                        'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                        'fill' => true
                    ],
                    [
                        'label' => 'Net Payable',
                        'data' => $reports->pluck('net_payable'),
                        'borderColor' => '#28a745',
                        'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                        'fill' => true
                    ]
                ]
            ];

            return response()->json($chartData);

        } catch (\Exception $e) {
            Log::error('Chart data error: ' . $e->getMessage(), [
                'operator_id' => $operator->id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return response()->json(['error' => 'Unable to load chart data'], 500);
        }
    }

    /**
     * Get revenue summary for AJAX
     */
    public function summary(Request $request)
    {
        $operator = auth('operator')->user();

        $days = $request->get('days', 30);

        try {
            $summary = $this->revenueCalculator->getRevenueSummary($operator->id, $days);

            return response()->json($summary);

        } catch (\Exception $e) {
            Log::error('Revenue summary error: ' . $e->getMessage(), [
                'operator_id' => $operator->id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return response()->json(['error' => 'Unable to load revenue summary'], 500);
        }
    }
}
