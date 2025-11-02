@extends('agent.layouts.app')

@section('panel')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">My Profile</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('agent.profile.update') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $agent->name) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $agent->email) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                    value="{{ old('phone', $agent->phone) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control">{{ old('address', $agent->address) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PAN Number</label>
                                    <input type="text" name="pan_number" class="form-control"
                                        value="{{ old('pan_number', $agent->pan_number) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Aadhaar Number</label>
                                    <input type="text" name="aadhaar_number" class="form-control"
                                        value="{{ old('aadhaar_number', $agent->aadhaar_number) }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Profile Image</label>
                                <input type="file" name="profile_image" class="form-control">
                                @if ($agent->profile_image)
                                    <img src="{{ asset('storage/' . $agent->profile_image) }}" alt="Profile"
                                        class="img-thumbnail mt-2" style="max-width:120px">
                                @endif
                            </div>

                            <div class="d-flex">
                                <button class="btn btn-primary mr-2">Save Changes</button>

                                @if ($agent->isActive)
                                    <form method="POST" action="{{ route('agent.profile.update') }}"
                                        style="display:inline">
                                        @csrf
                                        <input type="hidden" name="action" value="deactivate">
                                        <button class="btn btn-outline-warning"
                                            onclick="return confirm('Deactivate account?')">Deactivate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('agent.profile.update') }}"
                                        style="display:inline">
                                        @csrf
                                        <input type="hidden" name="action" value="activate">
                                        <button class="btn btn-outline-success">Activate</button>
                                    </form>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Documents</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('agent.profile.documents') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Upload Documents (PAN / Aadhaar / KYC)</label>
                                <input type="file" name="documents[]" class="form-control" multiple>
                            </div>
                            <button class="btn btn-primary">Upload</button>
                        </form>

                        @if (!empty($agent->documents) && is_array($agent->documents))
                            <div class="mt-3">
                                <h6>Existing Documents</h6>
                                <ul>
                                    @foreach ($agent->documents as $doc)
                                        <li><a href="{{ asset('storage/' . $doc) }}"
                                                target="_blank">{{ basename($doc) }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Account</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> {{ $agent->isActive ? 'Active' : ucfirst($agent->status) }}</p>
                        <p><strong>Total Bookings:</strong> {{ $agent->total_bookings ?? 0 }}</p>
                        <p><strong>Total Earnings:</strong> {{ number_format($agent->total_earnings ?? 0, 2) }}</p>
                        <p><strong>Pending Earnings:</strong> {{ number_format($agent->pending_earnings ?? 0, 2) }}</p>

                        <hr>

                        <form method="POST" action="{{ route('agent.logout') }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-block">Logout</button>
                        </form>

                        <a href="/policy/terms" class="btn btn-link mt-2">Terms & Conditions</a>
                        <a href="/policy/privacy" class="btn btn-link">Privacy Policy</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Bank Details</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">You currently don't have bank details saved. Use the profile edit form to add
                            bank details in the address or documents field, or contact admin to add payout settings.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
