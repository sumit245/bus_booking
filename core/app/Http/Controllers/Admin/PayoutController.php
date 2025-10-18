<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\OperatorPayout;
use App\Services\RevenueCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PayoutController extends Controller
{
    protected $revenueCalculator;

    public function __construct(RevenueCalculator $revenueCalculator)
    {
        $this->revenueCalculator = $revenueCalculator;
    }

    /**
     * Show all operator payouts
     */
    public function index(Request $request)
    {
        $pageTitle = 'Operator Payouts';
        $query = OperatorPayout::with(['operator', 'createdByAdmin']);

        // Apply filters
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->operator_id);
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->where('payout_period_start', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('payout_period_end', '<=', $request->end_date);
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get operators for filter dropdown
        $operators = Operator::orderBy('company_name')->get();

        // Calculate admin earnings summary
        $adminEarnings = [
            'total_pending_amount' => OperatorPayout::whereIn('payment_status', ['pending', 'partial'])->sum('pending_amount'),
            'total_paid_amount' => OperatorPayout::where('payment_status', 'paid')->sum('amount_paid'),
            'total_partial_amount' => OperatorPayout::where('payment_status', 'partial')->sum('amount_paid'),
            'total_platform_fees' => OperatorPayout::sum('platform_fee'),
            'total_payment_gateway_fees' => OperatorPayout::sum('payment_gateway_fee'),
            'total_tds_collected' => OperatorPayout::sum('tds_amount'),
            'pending_payouts_count' => OperatorPayout::whereIn('payment_status', ['pending', 'partial'])->count(),
            'paid_payouts_count' => OperatorPayout::where('payment_status', 'paid')->count(),
        ];

        return view('admin.payouts.index', compact('pageTitle', 'payouts', 'operators', 'adminEarnings'));
    }

    /**
     * Show payout details
     */
    public function show(OperatorPayout $payout)
    {
        $pageTitle = 'Payout Details';
        $payout->load(['operator', 'createdByAdmin']);

        return view('admin.payouts.show', compact('pageTitle', 'payout'));
    }

    /**
     * Show form to create new payout
     */
    public function create(Request $request)
    {
        $pageTitle = 'Generate Payout';
        $operatorId = $request->get('operator_id');
        $operators = Operator::orderBy('company_name')->get();

        return view('admin.payouts.create', compact('pageTitle', 'operators', 'operatorId'));
    }

    /**
     * Generate payout for operator
     */
    public function generate(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        try {
            $payout = $this->revenueCalculator->generatePayout(
                $request->operator_id,
                $request->start_date,
                $request->end_date
            );

            return redirect()->route('admin.payouts.show', $payout->id)
                ->with('success', 'Payout generated successfully for the selected period.');

        } catch (\Exception $e) {
            Log::error('Generate payout error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to generate payout. Please try again.');
        }
    }

    /**
     * Show form to record payment
     */
    public function paymentForm(OperatorPayout $payout)
    {
        $pageTitle = 'Record Payout Payment';
        $payout->load('operator');

        if ($payout->isPaid()) {
            return redirect()->route('admin.payouts.show', $payout->id)
                ->with('warning', 'This payout has already been fully paid.');
        }

        return view('admin.payouts.payment', compact('pageTitle', 'payout'));
    }

    /**
     * Record payment for payout
     */
    public function recordPayment(Request $request, OperatorPayout $payout)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:255',
            'transaction_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000'
        ]);

        if ($payout->isPaid()) {
            return redirect()->back()
                ->with('error', 'This payout has already been fully paid.');
        }

        if ($request->amount > $payout->pending_amount) {
            return redirect()->back()
                ->with('error', 'Payment amount cannot exceed pending amount.');
        }

        try {
            $payout->markAsPaid(
                $request->amount,
                $request->payment_method,
                $request->transaction_reference,
                $request->payment_notes
            );

            $status = $payout->isPaid() ? 'fully paid' : 'partially paid';

            return redirect()->route('admin.payouts.show', $payout->id)
                ->with('success', "Payment recorded successfully. Payout is now {$status}.");

        } catch (\Exception $e) {
            Log::error('Record payment error: ' . $e->getMessage(), [
                'payout_id' => $payoutId,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to record payment. Please try again.');
        }
    }

    /**
     * Cancel payout
     */
    public function cancel(OperatorPayout $payout)
    {

        if ($payout->isPaid()) {
            return redirect()->back()
                ->with('error', 'Cannot cancel a paid payout.');
        }

        try {
            $payout->update([
                'payment_status' => 'cancelled',
                'admin_notes' => $payout->admin_notes . "\n\nCancelled on " . now()->format('Y-m-d H:i:s')
            ]);

            return redirect()->route('admin.payouts.show', $payout->id)
                ->with('success', 'Payout cancelled successfully.');

        } catch (\Exception $e) {
            Log::error('Cancel payout error: ' . $e->getMessage(), [
                'payout_id' => $payoutId,
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to cancel payout. Please try again.');
        }
    }

    /**
     * Update payout notes
     */
    public function updateNotes(Request $request, OperatorPayout $payout)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:2000'
        ]);

        try {
            $payout->update([
                'admin_notes' => $request->admin_notes
            ]);

            return redirect()->route('admin.payouts.show', $payout->id)
                ->with('success', 'Notes updated successfully.');

        } catch (\Exception $e) {
            Log::error('Update payout notes error: ' . $e->getMessage(), [
                'payout_id' => $payoutId,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to update notes. Please try again.');
        }
    }

    /**
     * Get payout statistics for dashboard
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_payouts' => OperatorPayout::count(),
                'pending_payouts' => OperatorPayout::pending()->count(),
                'paid_payouts' => OperatorPayout::paid()->count(),
                'total_paid_amount' => OperatorPayout::paid()->sum('amount_paid'),
                'total_pending_amount' => OperatorPayout::whereIn('payment_status', ['pending', 'partial'])->sum('pending_amount'),
                'this_month_payouts' => OperatorPayout::whereMonth('created_at', Carbon::now()->month)->count(),
                'this_month_amount' => OperatorPayout::whereMonth('created_at', Carbon::now()->month)->sum('amount_paid')
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Payout statistics error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json(['error' => 'Unable to load statistics'], 500);
        }
    }

    /**
     * Export payouts data
     */
    public function export(Request $request)
    {
        $query = OperatorPayout::with(['operator']);

        // Apply same filters as index
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->operator_id);
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->where('payout_period_start', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('payout_period_end', '<=', $request->end_date);
        }

        $payouts = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $csv = "Payout Report\n";
        $csv .= "Generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";

        $csv .= "Operator,Period,Total Revenue,Platform Fee,Gateway Fee,TDS,Net Payable,Amount Paid,Pending,Status,Paid Date\n";

        foreach ($payouts as $payout) {
            $csv .= "\"{$payout->operator->business_name}\",";
            $csv .= "\"{$payout->payout_period}\",";
            $csv .= "₹" . number_format($payout->total_revenue, 2) . ",";
            $csv .= "₹" . number_format($payout->platform_fee, 2) . ",";
            $csv .= "₹" . number_format($payout->payment_gateway_fee, 2) . ",";
            $csv .= "₹" . number_format($payout->tds_amount, 2) . ",";
            $csv .= "₹" . number_format($payout->net_payable, 2) . ",";
            $csv .= "₹" . number_format($payout->amount_paid, 2) . ",";
            $csv .= "₹" . number_format($payout->pending_amount, 2) . ",";
            $csv .= ucfirst($payout->payment_status) . ",";
            $csv .= ($payout->paid_date ? $payout->paid_date->format('Y-m-d') : 'N/A') . "\n";
        }

        $filename = "payouts_report_" . now()->format('Y-m-d_H-i-s') . ".csv";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Bulk generate payouts for all operators
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        try {
            $operators = Operator::all();
            $generated = 0;
            $errors = [];

            foreach ($operators as $operator) {
                try {
                    $this->revenueCalculator->generatePayout(
                        $operator->id,
                        $request->start_date,
                        $request->end_date
                    );
                    $generated++;
                } catch (\Exception $e) {
                    $errors[] = "Operator {$operator->business_name}: " . $e->getMessage();
                }
            }

            $message = "Generated payouts for {$generated} operators.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return redirect()->route('admin.payouts.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Bulk generate payouts error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Unable to generate bulk payouts. Please try again.');
        }
    }
}
