<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Mail\OperatorWelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OperatorController extends Controller
{
    /**
     * Display a listing of operators.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $operators = Operator::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%");
        })->latest()->paginate(getPaginate());

        $pageTitle = "Manage Operators";
        $emptyMessage = "No operators found";

        return view('operators.index', compact('operators', 'pageTitle', 'emptyMessage'));
    }

    /**
     * Show the form for creating a new operator.
     */
    public function create()
    {
        $pageTitle = 'Add New Operator';
        return view('operators.create', compact('pageTitle'));
    }

    /**
     * Store a newly created operator.
     */
    public function store(Request $request)
    {
        try {
            // Validate all fields at once
            $validated = $request->validate([
                // Step 1: Basic Details
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:operators,email',
                'mobile' => 'required|string|max:20|unique:operators,mobile',
                'password' => 'required|string|min:6|confirmed',
                'address' => 'required|string',

                // Step 2: Company Details
                'company_name' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',

                // Step 3: Documents
                'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'pan_card' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'aadhaar_card_front' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'aadhaar_card_back' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'business_license' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',

                // Step 4: Bank Details
                'account_holder_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:50',
                'ifsc_code' => 'required|string|max:20',
                'bank_name' => 'required|string|max:255',
                'gst_number' => 'nullable|string|max:20',
                'cancelled_cheque' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Create operator
            $operator = new Operator();
            $operator->name = $validated['name'];
            $operator->email = $validated['email'];
            $operator->mobile = $validated['mobile'];
            $operator->password = bcrypt($validated['password']);
            $operator->address = $validated['address'];
            $operator->company_name = $validated['company_name'];
            $operator->city = $validated['city'];
            $operator->state = $validated['state'];
            $operator->account_holder_name = $validated['account_holder_name'];
            $operator->account_number = $validated['account_number'];
            $operator->ifsc_code = $validated['ifsc_code'];
            $operator->bank_name = $validated['bank_name'];
            $operator->gst_number = $validated['gst_number'] ?? null;
            $operator->status = 1; // Active
            $operator->all_details_completed = true;

            // Upload files
            $path = imagePath()['profile']['operator']['path'];
            $size = imagePath()['profile']['operator']['size'];

            $fileFields = [
                'photo' => 'image',
                'pan_card' => 'image',
                'aadhaar_card_front' => 'image',
                'aadhaar_card_back' => 'image',
                'business_license' => 'file',
                'cancelled_cheque' => 'image'
            ];

            foreach ($fileFields as $field => $type) {
                if ($request->hasFile($field)) {
                    try {
                        if ($type === 'image') {
                            $operator->{$field} = uploadImage($request->file($field), $path, $size);
                        } else {
                            $operator->{$field} = uploadFile($request->file($field), $path);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to upload {$field} for operator", [
                            'error' => $e->getMessage(),
                            'file' => $field
                        ]);
                        $notify[] = ['error', "Failed to upload {$field}. Please try again."];
                        return back()->withNotify($notify);
                    }
                }
            }

            $operator->save();

            // Send welcome email
            try {
                Mail::to($operator->email)->send(new OperatorWelcomeMail([
                    'name' => $operator->name,
                    'email' => $operator->email,
                    'password' => $validated['password'],
                    'login_url' => url('/admin/login'),
                ]));

                Log::info('Operator created and welcome email sent', [
                    'operator_id' => $operator->id,
                    'email' => $operator->email
                ]);

                $notify[] = ['success', 'Operator created successfully! Welcome email sent.'];
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email', [
                    'operator_id' => $operator->id,
                    'error' => $e->getMessage()
                ]);
                $notify[] = ['success', 'Operator created successfully, but welcome email could not be sent.'];
            }

            return redirect()->route('admin.fleet.operators.index')->withNotify($notify);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Operator creation validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['password', 'password_confirmation'])
            ]);

            $notify[] = ['error', 'Please check the form for errors and try again.'];
            return back()->withErrors($e->errors())->withNotify($notify);

        } catch (\Exception $e) {
            Log::error('Failed to create operator', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['password', 'password_confirmation'])
            ]);

            $notify[] = ['error', 'An unexpected error occurred. Please try again.'];
            return back()->withNotify($notify);
        }
    }

    /**
     * Display the specified operator.
     */
    public function show(Operator $operator)
    {
        $pageTitle = "Operator Details - " . $operator->name;
        return view('operators.show', compact('operator', 'pageTitle'));
    }

    /**
     * Show the form for editing the specified operator.
     */
    public function edit(Operator $operator)
    {
        $pageTitle = "Edit Operator - " . $operator->name;
        return view('operators.edit', compact('operator', 'pageTitle'));
    }

    /**
     * Update the specified operator.
     */
    public function update(Request $request, Operator $operator)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:operators,email,' . $operator->id,
                'mobile' => 'required|string|max:20',
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'ifsc_code' => 'nullable|string|max:20',
                'bank_name' => 'nullable|string|max:255',
                'gst_number' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:6|confirmed',

                // Optional file uploads
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'pan_card' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'aadhaar_card_front' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'aadhaar_card_back' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'business_license' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'cancelled_cheque' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Update basic fields
            $operator->name = $validated['name'];
            $operator->email = $validated['email'];
            $operator->mobile = $validated['mobile'];
            $operator->address = $validated['address'] ?? $operator->address;
            $operator->company_name = $validated['company_name'] ?? $operator->company_name;
            $operator->city = $validated['city'] ?? $operator->city;
            $operator->state = $validated['state'] ?? $operator->state;
            $operator->account_holder_name = $validated['account_holder_name'] ?? $operator->account_holder_name;
            $operator->account_number = $validated['account_number'] ?? $operator->account_number;
            $operator->ifsc_code = $validated['ifsc_code'] ?? $operator->ifsc_code;
            $operator->bank_name = $validated['bank_name'] ?? $operator->bank_name;
            $operator->gst_number = $validated['gst_number'] ?? $operator->gst_number;

            // Update password if provided
            if (!empty($validated['password'])) {
                $operator->password = bcrypt($validated['password']);
            }

            // Handle file uploads
            $path = imagePath()['profile']['operator']['path'];
            $size = imagePath()['profile']['operator']['size'];

            $fileFields = [
                'photo' => 'image',
                'pan_card' => 'image',
                'aadhaar_card_front' => 'image',
                'aadhaar_card_back' => 'image',
                'business_license' => 'file',
                'cancelled_cheque' => 'image'
            ];

            foreach ($fileFields as $field => $type) {
                if ($request->hasFile($field)) {
                    try {
                        if ($type === 'image') {
                            $operator->{$field} = uploadImage($request->file($field), $path, $size);
                        } else {
                            $operator->{$field} = uploadFile($request->file($field), $path);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to upload {$field} for operator update", [
                            'operator_id' => $operator->id,
                            'error' => $e->getMessage()
                        ]);
                        $notify[] = ['error', "Failed to upload {$field}. Please try again."];
                        return back()->withNotify($notify);
                    }
                }
            }

            $operator->save();

            Log::info('Operator updated successfully', [
                'operator_id' => $operator->id,
                'updated_fields' => array_keys($validated)
            ]);

            $notify[] = ['success', 'Operator updated successfully.'];
            return redirect()->route('admin.fleet.operators.index')->withNotify($notify);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Operator update validation failed', [
                'operator_id' => $operator->id,
                'errors' => $e->errors()
            ]);

            $notify[] = ['error', 'Please check the form for errors and try again.'];
            return back()->withErrors($e->errors())->withNotify($notify);

        } catch (\Exception $e) {
            Log::error('Failed to update operator', [
                'operator_id' => $operator->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $notify[] = ['error', 'An unexpected error occurred. Please try again.'];
            return back()->withNotify($notify);
        }
    }

    /**
     * Remove the specified operator.
     */
    public function destroy(Operator $operator)
    {
        try {
            // Delete associated files
            $fileFields = ['photo', 'pan_card', 'aadhaar_card_front', 'aadhaar_card_back', 'business_license', 'cancelled_cheque'];
            foreach ($fileFields as $field) {
                if ($operator->{$field}) {
                    $filePath = imagePath()['profile']['operator']['path'] . '/' . $operator->{$field};
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            $operatorId = $operator->id;
            $operator->delete();

            Log::info('Operator deleted successfully', [
                'operator_id' => $operatorId
            ]);

            $notify[] = ['success', 'Operator deleted successfully.'];
            return redirect()->route('admin.fleet.operators.index')->withNotify($notify);

        } catch (\Exception $e) {
            Log::error('Failed to delete operator', [
                'operator_id' => $operator->id,
                'error' => $e->getMessage()
            ]);

            $notify[] = ['error', 'Failed to delete operator. Please try again.'];
            return back()->withNotify($notify);
        }
    }
}