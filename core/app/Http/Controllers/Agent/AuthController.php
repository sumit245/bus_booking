<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\AgentCommissionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $commissionCalculator;

    public function __construct(AgentCommissionCalculator $commissionCalculator)
    {
        $this->commissionCalculator = $commissionCalculator;
    }

    /**
     * Show agent registration form
     */
    public function showRegistration()
    {
        if (auth('agent')->check()) {
            return redirect()->route('agent.dashboard');
        }

        return view('agent.auth.register');
    }

    /**
     * Handle agent registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:agents',
            'phone' => 'required|string|max:20|unique:agents',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'nullable|string|max:500',
            'pan_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // Create agent with pending status
        $agent = Agent::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'pan_number' => $request->pan_number,
            'status' => 'pending', // Requires admin verification
        ]);

        // Log the agent in
        Auth::guard('agent')->login($agent);

        return redirect()->route('agent.dashboard')
            ->with('success', 'Registration successful! Your account is pending admin verification.');
    }

    /**
     * Show agent login form
     */
    public function showLogin()
    {
        if (auth('agent')->check()) {
            return redirect()->route('agent.dashboard');
        }

        return view('agent.auth.login');
    }

    /**
     * Handle agent login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::guard('agent')->attempt($credentials, $remember)) {
            $agent = Auth::guard('agent')->user();

            // Check if agent is active
            if ($agent->status === 'suspended') {
                Auth::guard('agent')->logout();
                return redirect()->back()
                    ->withErrors(['email' => 'Your account has been suspended. Please contact admin.']);
            }

            if ($agent->status === 'pending') {
                return redirect()->route('agent.dashboard')
                    ->with('warning', 'Your account is pending admin verification. Some features may be limited.');
            }

            // Update last login
            $agent->update(['last_login_at' => now()]);

            return redirect()->intended(route('agent.dashboard'))
                ->with('success', 'Welcome back, ' . $agent->name . '!');
        }

        return redirect()->back()
            ->withErrors(['email' => 'Invalid credentials or account not verified.'])
            ->withInput($request->except('password'));
    }

    /**
     * Handle agent logout
     */
    public function logout(Request $request)
    {
        Auth::guard('agent')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agent.login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show agent dashboard (for pending agents)
     */
    public function dashboard()
    {
        $agent = Auth::guard('agent')->user();

        if ($agent->status === 'pending') {
            return view('agent.auth.pending', compact('agent'));
        }

        return redirect()->route('agent.dashboard');
    }
}
