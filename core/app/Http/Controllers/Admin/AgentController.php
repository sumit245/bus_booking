<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        return view('admin.agents.index');
    }

    public function create()
    {
        return view('admin.agents.create');
    }

    public function store(Request $request)
    {
        // TODO: Implement agent creation logic
        return redirect()->route('admin.agents.index')->with('success', 'Agent created successfully');
    }

    public function show($agent)
    {
        return view('admin.agents.show', compact('agent'));
    }

    public function edit($agent)
    {
        return view('admin.agents.edit', compact('agent'));
    }

    public function update(Request $request, $agent)
    {
        // TODO: Implement agent update logic
        return redirect()->back()->with('success', 'Agent updated successfully');
    }

    public function verify(Request $request, $agent)
    {
        // TODO: Implement agent verification logic
        return redirect()->back()->with('success', 'Agent verified successfully');
    }

    public function suspend(Request $request, $agent)
    {
        // TODO: Implement agent suspension logic
        return redirect()->back()->with('success', 'Agent suspended successfully');
    }

    public function bookings($agent)
    {
        return view('admin.agents.bookings', compact('agent'));
    }

    public function earnings($agent)
    {
        return view('admin.agents.earnings', compact('agent'));
    }
}
