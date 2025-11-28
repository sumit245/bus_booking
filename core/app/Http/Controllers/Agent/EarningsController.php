<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EarningsController extends Controller
{
    public function index(Request $request)
    {
        $agent = auth()->guard('agent')->user();
        $period = $request->get('period', 'last30');

        // Calculate date range based on period
        $dateRange = $this->getDateRange($period, $request);

        // Get revenue summary
        $revenueSummary = $this->getRevenueSummary($agent, $dateRange);

        // Get chart data (last 30 days)
        $chartData = $this->getChartData($agent);

        return view('agent.earnings.index', compact('revenueSummary', 'chartData', 'period'));
    }

    private function getDateRange($period, $request)
    {
        switch ($period) {
            case 'today':
                return [
                    'start' => now()->startOfDay(),
                    'end' => now()->endOfDay()
                ];
            case 'last7':
                return [
                    'start' => now()->subDays(7)->startOfDay(),
                    'end' => now()->endOfDay()
                ];
            case 'last30':
                return [
                    'start' => now()->subDays(30)->startOfDay(),
                    'end' => now()->endOfDay()
                ];
            case 'this_month':
                return [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth()
                ];
            case 'last_month':
                return [
                    'start' => now()->subMonth()->startOfMonth(),
                    'end' => now()->subMonth()->endOfMonth()
                ];
            case 'custom':
                return [
                    'start' => $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date'))->startOfDay() : now()->subDays(30),
                    'end' => $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date'))->endOfDay() : now()
                ];
            case 'all':
            default:
                return [
                    'start' => null,
                    'end' => null
                ];
        }
    }

    private function getRevenueSummary($agent, $dateRange)
    {
        $query = BookedTicket::where('agent_id', $agent->id);

        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        $tickets = $query->get();

        $totalTickets = $tickets->count();
        $confirmedTickets = $tickets->where('status', 1)->count();
        $cancelledTickets = $tickets->where('status', 2)->count();
        $pendingTickets = $tickets->where('status', 0)->count();

        $totalCommission = $tickets->sum('agent_commission_amount');
        $confirmedCommission = $tickets->where('status', 1)->sum('agent_commission_amount');
        $pendingCommission = $tickets->where('status', 0)->sum('agent_commission_amount');

        return [
            'summary' => [
                'total_tickets' => $totalTickets,
                'confirmed_tickets' => $confirmedTickets,
                'cancelled_tickets' => $cancelledTickets,
                'pending_tickets' => $pendingTickets,
                'total_commission' => $totalCommission,
                'confirmed_commission' => $confirmedCommission,
                'pending_commission' => $pendingCommission,
            ]
        ];
    }

    private function getChartData($agent)
    {
        $last30Days = [];
        for ($i = 29; $i >= 0; $i--) {
            $last30Days[] = now()->subDays($i)->format('Y-m-d');
        }

        $earnings = BookedTicket::where('agent_id', $agent->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(agent_commission_amount) as commission')
            ->groupBy('date')
            ->pluck('commission', 'date');

        $chartData = [];
        foreach ($last30Days as $date) {
            $chartData[] = $earnings->get($date, 0);
        }

        return [
            'labels' => $last30Days,
            'data' => $chartData
        ];
    }

    public function chartData()
    {
        $agent = auth()->guard('agent')->user();
        $chartData = $this->getChartData($agent);

        return response()->json([
            'labels' => array_map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('M d');
            }, $chartData['labels']),
            'datasets' => [
                [
                    'label' => 'Commission Earned',
                    'data' => $chartData['data'],
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'tension' => 0.1
                ]
            ]
        ]);
    }

    public function export(Request $request)
    {
        $agent = auth()->guard('agent')->user();
        $period = $request->get('period', 'last30');
        $dateRange = $this->getDateRange($period, $request);

        $query = BookedTicket::where('agent_id', $agent->id);

        if ($dateRange['start']) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        if ($dateRange['end']) {
            $query->where('created_at', '<=', $dateRange['end']);
        }

        $tickets = $query->get();
        $filename = 'agent_earnings_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['Date', 'Booking ID', 'Ticket No', 'Journey Date', 'Commission', 'Status', 'Payment Status'];

        $callback = function () use ($tickets, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->booking_id ?? '',
                    $ticket->ticket_no ?? '',
                    $ticket->date_of_journey ?? '',
                    $ticket->agent_commission_amount ?? 0,
                    $ticket->status == 1 ? 'Confirmed' : ($ticket->status == 2 ? 'Cancelled' : 'Pending'),
                    $ticket->payment_status ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
