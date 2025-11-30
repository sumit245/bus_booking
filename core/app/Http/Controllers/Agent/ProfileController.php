<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

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
        $agent = auth()->guard('agent')->user();

        // Handle account activation/deactivation
        if ($request->has('action')) {
            if ($request->action === 'deactivate') {
                $agent->update(['status' => 'suspended']);
                return redirect()->back()->with('success', 'Account deactivated successfully');
            } elseif ($request->action === 'activate') {
                $agent->update(['status' => 'active']);
                return redirect()->back()->with('success', 'Account activated successfully');
            }
        }

        // Validate profile update data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:agents,email,' . $agent->id,
            'phone' => 'required|string|max:20|unique:agents,phone,' . $agent->id,
            'address' => 'nullable|string|max:500',
            'pan_number' => 'nullable|string|max:20',
            'aadhaar_number' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image
            if ($agent->profile_image) {
                Storage::disk('public')->delete($agent->profile_image);
            }

            $path = $request->file('profile_image')->store('agents/profiles', 'public');
            $validated['profile_image'] = $path;
        }

        $agent->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function uploadDocuments(Request $request)
    {
        $agent = auth()->guard('agent')->user();

        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $documents = $agent->documents ?? [];

        foreach ($request->file('documents') as $key => $file) {
            $path = $file->store('agents/documents/' . $agent->id, 'public');
            $documents[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        $agent->update(['documents' => $documents]);

        return redirect()->back()->with('success', 'Documents uploaded successfully');
    }

    public function changePassword(Request $request)
    {
        $agent = auth()->guard('agent')->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $agent->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        // Update password
        $agent->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->back()->with('success', 'Password changed successfully');
    }
}
