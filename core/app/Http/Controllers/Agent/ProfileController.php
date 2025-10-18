<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        return view('agent.profile.index');
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
