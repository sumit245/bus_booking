<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function busSearch(Request $request)
    {
        // TODO: Implement API bus search logic
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getSchedules($bus)
    {
        // TODO: Implement API schedules logic
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getSeatLayout($bus, $schedule)
    {
        // TODO: Implement API seat layout logic
        return response()->json(['success' => true, 'data' => []]);
    }

    public function createBooking(Request $request)
    {
        // TODO: Implement API booking creation logic
        return response()->json(['success' => true, 'data' => []]);
    }

    public function calculateCommission(Request $request)
    {
        // TODO: Implement API commission calculation logic
        return response()->json(['success' => true, 'data' => []]);
    }
}
