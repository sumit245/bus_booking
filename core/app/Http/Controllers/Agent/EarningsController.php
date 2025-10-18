<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function index()
    {
        return view('agent.earnings.index');
    }

    public function monthly()
    {
        return view('agent.earnings.monthly');
    }

    public function export()
    {
        // TODO: Implement earnings export logic
        return redirect()->back()->with('success', 'Earnings exported successfully');
    }
}
