<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RevenueReport;
use App\Models\OperatorPayout;
use App\Services\RevenueCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
    public function dashboard()
    {
        $pageTitle = 'Revenue Dashboard';
        $operator = auth('operator')->user();

        try {
            // Get revenue summary for last 30 days
            $revenueSummary = $this->revenueCalculator->getRevenueSummary($operator->id, 30);

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

        $reports = $query->orderBy('report_date', 'desc')->paginate(20);

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
     * Export revenue data
     */
    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,json'
        ]);

        $operator = auth('operator')->user();

        try {
            $data = $this->revenueCalculator->exportRevenueData(
                $operator->id,
                $request->start_date,
                $request->end_date,
                $request->format
            );

            if ($request->format === 'csv') {
                $filename = "revenue_report_{$operator->id}_{$request->start_date}_to_{$request->end_date}.csv";
                return response($data)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
            } else {
                $filename = "revenue_report_{$operator->id}_{$request->start_date}_to_{$request->end_date}.json";
                return response($data)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
            }

        } catch (\Exception $e) {
            Log::error('Export revenue data error: ' . $e->getMessage(), [
                'operator_id' => $operator->id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to export revenue data. Please try again.');
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
