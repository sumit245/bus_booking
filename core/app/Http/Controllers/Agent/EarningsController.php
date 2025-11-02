<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function index(Request $request)
    {
        $agent = auth()->guard('agent')->user();

        $query = $agent->agentBookings()->with(['bookedTicket']);

        if ($request->filled('date_from')) {
            $query->whereHas('bookedTicket', function ($q) use ($request) {
                $q->whereDate('date_of_journey', '>=', $request->get('date_from'));
            });
        }
        if ($request->filled('date_to')) {
            $query->whereHas('bookedTicket', function ($q) use ($request) {
                $q->whereDate('date_of_journey', '<=', $request->get('date_to'));
            });
        }

        $query->orderByDesc('created_at');

        // Export CSV path
        if ($request->boolean('export')) {
            $rows = $query->get();
            $filename = 'agent_earnings_' . now()->format('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $columns = ['Date', 'Booking ID', 'Ticket No', 'Commission', 'Payment Status', 'Commission Paid At'];

            $callback = function () use ($rows, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
                foreach ($rows as $row) {
                    $bt = $row->bookedTicket;
                    fputcsv($file, [
                        $row->created_at->toDateTimeString(),
                        $bt->booking_id ?? '',
                        $bt->ticket_no ?? '',
                        $row->total_commission_earned ?? '',
                        $row->payment_status ?? '',
                        optional($row->commission_paid_at)->toDateTimeString() ?? '',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        $transactions = $query->paginate(20)->appends($request->query());

        return view('agent.earnings.index', compact('transactions'));
    }

    public function monthly()
    {
        return view('agent.earnings.monthly');
    }

    public function export(Request $request)
    {
        // Keep compatibility: redirect to index with export flag so single export logic is used
        return redirect()->route('agent.earnings', array_merge($request->query(), ['export' => 1]));
    }
}
