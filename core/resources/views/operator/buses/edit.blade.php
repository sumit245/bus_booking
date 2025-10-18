@extends('operator.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Edit Bus') - {{ $bus->display_name }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.buses.update', $bus->id) }}" method="POST" id="busForm">
                        @csrf
                        @method('PUT')

                        <!-- Basic Bus Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Bus Number') <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('bus_number') is-invalid @enderror"
                                        name="bus_number" value="{{ old('bus_number', $bus->bus_number) }}" required
                                        placeholder="e.g., MP2921">
                                    @error('bus_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Travel Name') <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('travel_name') is-invalid @enderror"
                                        name="travel_name" value="{{ old('travel_name', $bus->travel_name) }}" required
                                        placeholder="e.g., Kalpana Travels Rewa">
                                    @error('travel_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('This should match your company name for consistency.')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Bus Type') <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('bus_type') is-invalid @enderror"
                                        name="bus_type" value="{{ old('bus_type', $bus->bus_type) }}" required
                                        placeholder="e.g., Non Ac Seater (2+2)">
                                    @error('bus_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Service Name')</label>
                                    <input type="text" class="form-control @error('service_name') is-invalid @enderror"
                                        name="service_name" value="{{ old('service_name', $bus->service_name) }}"
                                        placeholder="e.g., Seat Seller">
                                    @error('service_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Current Route')</label>
                                    <select class="form-control @error('current_route_id') is-invalid @enderror"
                                        name="current_route_id" id="current_route_id">
                                        <option value="">@lang('Select Route (Optional)')</option>
                                        @foreach ($routes as $route)
                                            <option value="{{ $route->id }}"
                                                {{ old('current_route_id', $bus->current_route_id) == $route->id ? 'selected' : '' }}>
                                                {{ $route->originCity->city_name ?? 'N/A' }} →
                                                {{ $route->destinationCity->city_name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('current_route_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Fuel Type')</label>
                                    <select class="form-control @error('fuel_type') is-invalid @enderror" name="fuel_type">
                                        <option value="Diesel"
                                            {{ old('fuel_type', $bus->fuel_type) == 'Diesel' ? 'selected' : '' }}>
                                            @lang('Diesel')</option>
                                        <option value="Petrol"
                                            {{ old('fuel_type', $bus->fuel_type) == 'Petrol' ? 'selected' : '' }}>
                                            @lang('Petrol')</option>
                                        <option value="CNG"
                                            {{ old('fuel_type', $bus->fuel_type) == 'CNG' ? 'selected' : '' }}>
                                            @lang('CNG')</option>
                                        <option value="Electric"
                                            {{ old('fuel_type', $bus->fuel_type) == 'Electric' ? 'selected' : '' }}>
                                            @lang('Electric')</option>
                                    </select>
                                    @error('fuel_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Seat Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Seat Information')</h5>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Total Seats') <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('total_seats') is-invalid @enderror"
                                        name="total_seats" value="{{ old('total_seats', $bus->total_seats) }}" required
                                        min="1" max="100">
                                    @error('total_seats')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Available Seats') <span class="text-danger">*</span></label>
                                    <input type="number"
                                        class="form-control @error('available_seats') is-invalid @enderror"
                                        name="available_seats" value="{{ old('available_seats', $bus->available_seats) }}"
                                        required min="0">
                                    @error('available_seats')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Max Seats Per Ticket')</label>
                                    <input type="number"
                                        class="form-control @error('max_seats_per_ticket') is-invalid @enderror"
                                        name="max_seats_per_ticket"
                                        value="{{ old('max_seats_per_ticket', $bus->max_seats_per_ticket) }}"
                                        min="1" max="20">
                                    @error('max_seats_per_ticket')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Pricing Information')</h5>
                                <div class="alert alert-info">
                                    <i class="la la-info-circle"></i>
                                    <strong>@lang('Pricing Formula'):</strong> Published Price = Offered Price + Agent Commission
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Base Price (₹)') <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('base_price') is-invalid @enderror" name="base_price"
                                        value="{{ old('base_price', $bus->base_price) }}" required min="0"
                                        id="base_price">
                                    @error('base_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Base fare for the route')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Published Price (₹)') <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('published_price') is-invalid @enderror"
                                        name="published_price"
                                        value="{{ old('published_price', $bus->published_price) }}" required
                                        min="0" id="published_price">
                                    @error('published_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Price shown to customers')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Offered Price (₹)') <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('offered_price') is-invalid @enderror"
                                        name="offered_price" value="{{ old('offered_price', $bus->offered_price) }}"
                                        required min="0" id="offered_price">
                                    @error('offered_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Minimum price you can offer')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Agent Commission (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('agent_commission') is-invalid @enderror"
                                        name="agent_commission"
                                        value="{{ old('agent_commission', $bus->agent_commission) }}" min="0"
                                        id="agent_commission" readonly>
                                    @error('agent_commission')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Auto-calculated: Published - Offered')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Tax (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('tax') is-invalid @enderror" name="tax"
                                        value="{{ old('tax', $bus->tax ?? 0) }}" min="0" id="tax">
                                    @error('tax')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Tax amount')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Other Charges (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('other_charges') is-invalid @enderror"
                                        name="other_charges" value="{{ old('other_charges', $bus->other_charges ?? 0) }}"
                                        min="0" id="other_charges">
                                    @error('other_charges')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Additional charges')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Discount (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('discount') is-invalid @enderror" name="discount"
                                        value="{{ old('discount', $bus->discount ?? 0) }}" min="0"
                                        id="discount">
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Discount amount')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Service Charges (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('service_charges') is-invalid @enderror"
                                        name="service_charges"
                                        value="{{ old('service_charges', $bus->service_charges) }}" min="0"
                                        id="service_charges">
                                    @error('service_charges')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Service charges')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('TDS (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('tds') is-invalid @enderror" name="tds"
                                        value="{{ old('tds', $bus->tds) }}" min="0" id="tds">
                                    @error('tds')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Tax Deducted at Source')</small>
                                </div>
                            </div>
                        </div>

                        <!-- GST Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('GST Information')</h5>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('CGST Amount (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('cgst_amount') is-invalid @enderror"
                                        name="cgst_amount" value="{{ old('cgst_amount', $bus->cgst_amount ?? 0) }}"
                                        min="0" id="cgst_amount">
                                    @error('cgst_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Central GST Amount')</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('CGST Rate (%)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('cgst_rate') is-invalid @enderror" name="cgst_rate"
                                        value="{{ old('cgst_rate', $bus->cgst_rate ?? 0) }}" min="0"
                                        max="100" id="cgst_rate">
                                    @error('cgst_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Central GST Rate')</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('IGST Amount (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('igst_amount') is-invalid @enderror"
                                        name="igst_amount" value="{{ old('igst_amount', $bus->igst_amount ?? 0) }}"
                                        min="0" id="igst_amount">
                                    @error('igst_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Integrated GST Amount')</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('IGST Rate (%)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('igst_rate') is-invalid @enderror" name="igst_rate"
                                        value="{{ old('igst_rate', $bus->igst_rate ?? 0) }}" min="0"
                                        max="100" id="igst_rate">
                                    @error('igst_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Integrated GST Rate')</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('SGST Amount (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('sgst_amount') is-invalid @enderror"
                                        name="sgst_amount" value="{{ old('sgst_amount', $bus->sgst_amount ?? 0) }}"
                                        min="0" id="sgst_amount">
                                    @error('sgst_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('State GST Amount')</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('SGST Rate (%)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('sgst_rate') is-invalid @enderror" name="sgst_rate"
                                        value="{{ old('sgst_rate', $bus->sgst_rate ?? 0) }}" min="0"
                                        max="100" id="sgst_rate">
                                    @error('sgst_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('State GST Rate')</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('Taxable Amount (₹)')</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('taxable_amount') is-invalid @enderror"
                                        name="taxable_amount"
                                        value="{{ old('taxable_amount', $bus->taxable_amount ?? 0) }}" min="0"
                                        id="taxable_amount">
                                    @error('taxable_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">@lang('Amount on which GST is calculated')</small>
                                </div>
                            </div>
                        </div>

                        <!-- Bus Features -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Bus Features')</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="id_proof_required"
                                            name="id_proof_required" value="1"
                                            {{ old('id_proof_required', $bus->id_proof_required) ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="id_proof_required">@lang('ID Proof Required')</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_drop_point_mandatory"
                                            name="is_drop_point_mandatory" value="1"
                                            {{ old('is_drop_point_mandatory', $bus->is_drop_point_mandatory) ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="is_drop_point_mandatory">@lang('Drop Point Mandatory')</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="live_tracking_available"
                                            name="live_tracking_available" value="1"
                                            {{ old('live_tracking_available', $bus->live_tracking_available) ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="live_tracking_available">@lang('Live Tracking Available')</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="m_ticket_enabled"
                                            name="m_ticket_enabled" value="1"
                                            {{ old('m_ticket_enabled', $bus->m_ticket_enabled) ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="m_ticket_enabled">@lang('M-Ticket Enabled')</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input"
                                            id="partial_cancellation_allowed" name="partial_cancellation_allowed"
                                            value="1"
                                            {{ old('partial_cancellation_allowed', $bus->partial_cancellation_allowed) ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="partial_cancellation_allowed">@lang('Partial Cancellation Allowed')</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="status"
                                            name="status" value="1"
                                            {{ old('status', $bus->status) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="status">@lang('Active')</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bus Details -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Bus Details')</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Manufacturing Year')</label>
                                    <input type="number"
                                        class="form-control @error('manufacturing_year') is-invalid @enderror"
                                        name="manufacturing_year"
                                        value="{{ old('manufacturing_year', $bus->manufacturing_year) }}" min="1990"
                                        max="{{ date('Y') + 1 }}">
                                    @error('manufacturing_year')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Amenities')</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="amenity_ac"
                                                    name="amenities[]" value="AC"
                                                    {{ in_array('AC', old('amenities', $bus->amenities ?? [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="amenity_ac">@lang('AC')</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="amenity_wifi"
                                                    name="amenities[]" value="WiFi"
                                                    {{ in_array('WiFi', old('amenities', $bus->amenities ?? [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="amenity_wifi">@lang('WiFi')</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="amenity_tv"
                                                    name="amenities[]" value="TV"
                                                    {{ in_array('TV', old('amenities', $bus->amenities ?? [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="amenity_tv">@lang('TV')</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="amenity_charging"
                                                    name="amenities[]" value="Charging"
                                                    {{ in_array('Charging', old('amenities', $bus->amenities ?? [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="amenity_charging">@lang('Charging')</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="amenity_blanket"
                                                    name="amenities[]" value="Blanket"
                                                    {{ in_array('Blanket', old('amenities', $bus->amenities ?? [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="amenity_blanket">@lang('Blanket')</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="amenity_water"
                                                    name="amenities[]" value="Water"
                                                    {{ in_array('Water', old('amenities', $bus->amenities ?? [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="amenity_water">@lang('Water')</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Description')</label>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                        placeholder="@lang('Additional bus details (optional)')">{{ old('description', $bus->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Documentation -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">@lang('Documentation')</h5>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Insurance Number')</label>
                                    <input type="text"
                                        class="form-control @error('insurance_number') is-invalid @enderror"
                                        name="insurance_number"
                                        value="{{ old('insurance_number', $bus->insurance_number) }}">
                                    @error('insurance_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Insurance Expiry')</label>
                                    <input type="date"
                                        class="form-control @error('insurance_expiry') is-invalid @enderror"
                                        name="insurance_expiry"
                                        value="{{ old('insurance_expiry', $bus->insurance_expiry ? $bus->insurance_expiry->format('Y-m-d') : '') }}">
                                    @error('insurance_expiry')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Permit Number')</label>
                                    <input type="text"
                                        class="form-control @error('permit_number') is-invalid @enderror"
                                        name="permit_number" value="{{ old('permit_number', $bus->permit_number) }}">
                                    @error('permit_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Permit Expiry')</label>
                                    <input type="date"
                                        class="form-control @error('permit_expiry') is-invalid @enderror"
                                        name="permit_expiry"
                                        value="{{ old('permit_expiry', $bus->permit_expiry ? $bus->permit_expiry->format('Y-m-d') : '') }}">
                                    @error('permit_expiry')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Fitness Certificate')</label>
                                    <input type="text"
                                        class="form-control @error('fitness_certificate') is-invalid @enderror"
                                        name="fitness_certificate"
                                        value="{{ old('fitness_certificate', $bus->fitness_certificate) }}">
                                    @error('fitness_certificate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('Fitness Expiry')</label>
                                    <input type="date"
                                        class="form-control @error('fitness_expiry') is-invalid @enderror"
                                        name="fitness_expiry"
                                        value="{{ old('fitness_expiry', $bus->fitness_expiry ? $bus->fitness_expiry->format('Y-m-d') : '') }}">
                                    @error('fitness_expiry')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn--primary">@lang('Update Bus')</button>
                            <a href="{{ route('operator.buses.index') }}"
                                class="btn btn--secondary">@lang('Cancel')</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Auto-calculate available seats based on total seats
            $('input[name="total_seats"]').on('input', function() {
                const totalSeats = parseInt($(this).val()) || 0;
                const availableSeatsInput = $('input[name="available_seats"]');
                const currentAvailable = parseInt(availableSeatsInput.val()) || 0;

                if (currentAvailable > totalSeats) {
                    availableSeatsInput.val(totalSeats);
                }
                availableSeatsInput.attr('max', totalSeats);
            });

            // Validate available seats doesn't exceed total seats
            $('input[name="available_seats"]').on('input', function() {
                const totalSeats = parseInt($('input[name="total_seats"]').val()) || 0;
                const availableSeats = parseInt($(this).val()) || 0;

                if (availableSeats > totalSeats) {
                    $(this).val(totalSeats);
                    alert('Available seats cannot exceed total seats');
                }
            });

            // Auto-calculate agent commission based on published and offered prices
            function calculateAgentCommission() {
                const publishedPrice = parseFloat($('#published_price').val()) || 0;
                const offeredPrice = parseFloat($('#offered_price').val()) || 0;
                const agentCommission = publishedPrice - offeredPrice;

                $('#agent_commission').val(agentCommission >= 0 ? agentCommission.toFixed(2) : 0);
            }

            // Calculate agent commission when published price changes
            $('#published_price').on('input', function() {
                calculateAgentCommission();
            });

            // Calculate agent commission when offered price changes
            $('#offered_price').on('input', function() {
                calculateAgentCommission();
            });

            // Calculate initial agent commission on page load
            calculateAgentCommission();
        });
    </script>
@endpush

@push('breadcrumb-plugins')
    <a href="{{ route('operator.buses.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
