<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        $agent = auth()->guard('agent')->user();

        // Provide the agent model to the view so profile fields can be shown/edited
        return view('agent.profile.index', compact('agent'));
    }

    public function update(Request $request)
    {
        // TODO: Implement profile update logic
        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function uploadDocuments(Request $request)
    {
        // TODO: Implement document upload logic
        return redirect()->back()->with('success', 'Documents uploaded successfully');
    }
}
