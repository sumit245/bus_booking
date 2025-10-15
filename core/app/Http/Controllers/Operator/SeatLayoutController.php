<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\OperatorBus;
use App\Models\SeatLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SeatLayoutController extends Controller
{
    /**
     * Display a listing of seat layouts for a bus
     */
    public function index(OperatorBus $bus)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        $seatLayouts = $bus->seatLayouts()->orderBy('created_at', 'desc')->get();

        $pageTitle = 'Seat Layouts - ' . $bus->bus_number;

        return view('operator.seat-layouts.index', compact('bus', 'seatLayouts', 'pageTitle'));
    }

    /**
     * Show the form for creating a new seat layout
     */
    public function create(OperatorBus $bus)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        $pageTitle = 'Create Seat Layout - ' . $bus->bus_number;

        return view('operator.seat-layouts.create', compact('bus', 'pageTitle'));
    }

    /**
     * Store a newly created seat layout
     */
    public function store(Request $request, OperatorBus $bus)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator
        if ($bus->operator_id !== $operator->id) {
            abort(403, 'Unauthorized access to this bus.');
        }

        $validated = $request->validate([
            'layout_name' => 'required|string|max:255',
            'deck_type' => 'required|string|in:single,double',
            'seat_layout' => 'required|string|in:2x1,2x2,2x3,3x2,3x3,custom',
            'columns_per_row' => 'required|integer|min:4|max:12',
            'layout_data' => 'required|json',
            'total_seats' => 'required|integer|min:1|max:100',
            'upper_deck_seats' => 'required|integer|min:0',
            'lower_deck_seats' => 'required|integer|min:0',
        ]);

        try {
            // Deactivate all existing layouts for this bus
            $bus->seatLayouts()->update(['is_active' => false]);

            // Parse layout data
            $layoutData = json_decode($validated['layout_data'], true);

            // Create new seat layout
            $seatLayout = new SeatLayout([
                'operator_bus_id' => $bus->id,
                'layout_name' => $validated['layout_name'],
                'deck_type' => $validated['deck_type'],
                'seat_layout' => $validated['seat_layout'],
                'columns_per_row' => $validated['columns_per_row'],
                'total_seats' => $validated['total_seats'],
                'upper_deck_seats' => $validated['upper_deck_seats'],
                'lower_deck_seats' => $validated['lower_deck_seats'],
                'layout_data' => $layoutData,
                'is_active' => true
            ]);

            // Generate HTML layout
            $seatLayout->html_layout = $seatLayout->generateHtmlLayout();
            $seatLayout->save();

            // Update bus total seats
            $bus->update(['total_seats' => $validated['total_seats']]);

            Log::info('Seat layout created successfully', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'layout_id' => $seatLayout->id
            ]);

            return redirect()->route('operator.buses.seat-layouts.index', $bus)
                ->with('success', 'Seat layout created successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to create seat layout', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to create seat layout. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Display the specified seat layout
     */
    public function show(OperatorBus $bus, SeatLayout $seatLayout)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator and layout belongs to the bus
        if ($bus->operator_id !== $operator->id || $seatLayout->operator_bus_id !== $bus->id) {
            abort(403, 'Unauthorized access.');
        }

        $pageTitle = 'Seat Layout - ' . $seatLayout->layout_name;

        return view('operator.seat-layouts.show', compact('bus', 'seatLayout', 'pageTitle'));
    }

    /**
     * Show the form for editing the specified seat layout
     */
    public function edit(OperatorBus $bus, SeatLayout $seatLayout)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator and layout belongs to the bus
        if ($bus->operator_id !== $operator->id || $seatLayout->operator_bus_id !== $bus->id) {
            abort(403, 'Unauthorized access.');
        }

        $pageTitle = 'Edit Seat Layout - ' . $seatLayout->layout_name;

        return view('operator.seat-layouts.edit', compact('bus', 'seatLayout', 'pageTitle'));
    }

    /**
     * Update the specified seat layout
     */
    public function update(Request $request, OperatorBus $bus, SeatLayout $seatLayout)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator and layout belongs to the bus
        if ($bus->operator_id !== $operator->id || $seatLayout->operator_bus_id !== $bus->id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'layout_name' => 'required|string|max:255',
            'deck_type' => 'required|string|in:single,double',
            'layout_data' => 'required|json',
            'total_seats' => 'required|integer|min:1|max:100',
            'upper_deck_seats' => 'required|integer|min:0',
            'lower_deck_seats' => 'required|integer|min:0',
        ]);

        try {
            // Parse layout data
            $layoutData = json_decode($validated['layout_data'], true);

            // Update seat layout
            $seatLayout->update([
                'layout_name' => $validated['layout_name'],
                'deck_type' => $validated['deck_type'],
                'total_seats' => $validated['total_seats'],
                'upper_deck_seats' => $validated['upper_deck_seats'],
                'lower_deck_seats' => $validated['lower_deck_seats'],
                'layout_data' => $layoutData
            ]);

            // Regenerate HTML layout
            $seatLayout->html_layout = $seatLayout->generateHtmlLayout();
            $seatLayout->save();

            // Update bus total seats
            $bus->update(['total_seats' => $validated['total_seats']]);

            Log::info('Seat layout updated successfully', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'layout_id' => $seatLayout->id
            ]);

            return redirect()->route('operator.buses.seat-layouts.index', $bus)
                ->with('success', 'Seat layout updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update seat layout', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'layout_id' => $seatLayout->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to update seat layout. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Remove the specified seat layout
     */
    public function destroy(OperatorBus $bus, SeatLayout $seatLayout)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator and layout belongs to the bus
        if ($bus->operator_id !== $operator->id || $seatLayout->operator_bus_id !== $bus->id) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $seatLayout->delete();

            Log::info('Seat layout deleted successfully', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'layout_id' => $seatLayout->id
            ]);

            return redirect()->route('operator.buses.seat-layouts.index', $bus)
                ->with('success', 'Seat layout deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to delete seat layout', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'layout_id' => $seatLayout->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to delete seat layout. Please try again.']);
        }
    }

    /**
     * Toggle active status of a seat layout
     */
    public function toggleStatus(OperatorBus $bus, SeatLayout $seatLayout)
    {
        $operator = Auth::guard('operator')->user();

        // Ensure the bus belongs to the operator and layout belongs to the bus
        if ($bus->operator_id !== $operator->id || $seatLayout->operator_bus_id !== $bus->id) {
            abort(403, 'Unauthorized access.');
        }

        try {
            if ($seatLayout->is_active) {
                // Deactivate this layout
                $seatLayout->update(['is_active' => false]);
                $message = 'Seat layout deactivated successfully!';
            } else {
                // Deactivate all other layouts and activate this one
                $bus->seatLayouts()->update(['is_active' => false]);
                $seatLayout->update(['is_active' => true]);
                $message = 'Seat layout activated successfully!';
            }

            Log::info('Seat layout status toggled', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'layout_id' => $seatLayout->id,
                'is_active' => $seatLayout->is_active
            ]);

            return redirect()->route('operator.buses.seat-layouts.index', $bus)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to toggle seat layout status', [
                'operator_id' => $operator->id,
                'bus_id' => $bus->id,
                'layout_id' => $seatLayout->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to update seat layout status. Please try again.']);
        }
    }

    /**
     * Preview the seat layout (for AJAX requests)
     */
    public function preview(Request $request)
    {
        try {
            $validated = $request->validate([
                'layout_data' => 'required|json',
            ]);

            $layoutData = json_decode($validated['layout_data'], true);

            Log::info('Preview request received', [
                'layout_data' => $layoutData
            ]);

            // Create a temporary seat layout instance to generate HTML
            $tempLayout = new SeatLayout(['layout_data' => $layoutData]);
            $htmlLayout = $tempLayout->generateHtmlLayout();

            Log::info('Preview generated successfully', [
                'html_length' => strlen($htmlLayout)
            ]);

            return response()->json([
                'success' => true,
                'html_layout' => $htmlLayout,
                'processed_layout' => $tempLayout->layout_data
            ]);

        } catch (\Exception $e) {
            Log::error('Preview generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate preview: ' . $e->getMessage()
            ], 400);
        }
    }
}