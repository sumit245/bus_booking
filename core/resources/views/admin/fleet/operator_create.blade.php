@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('admin.fleet.operators.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <!-- Tab navigation -->
                                <ul class="nav nav-tabs" id="operatorTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="basic-details-tab" data-bs-toggle="tab"
                                            data-bs-target="#basic-details" type="button"
                                            role="tab">@lang('Basic Details')</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="documents-tab" data-bs-toggle="tab"
                                            data-bs-target="#documents" type="button"
                                            role="tab">@lang('Documents')</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="bank-details-tab" data-bs-toggle="tab"
                                            data-bs-target="#bank-details" type="button"
                                            role="tab">@lang('Bank Details')</button>
                                    </li>
                                </ul>

                                <!-- Tab content -->
                                <div class="tab-content mt-4" id="operatorTabsContent">
                                    <!-- Basic Details Tab -->
                                    <div class="tab-pane fade show active" id="basic-details" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Full Name')</label>
                                                    <input type="text" class="form-control" name="name"
                                                        value="{{ old('name') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Email')</label>
                                                    <input type="email" class="form-control" name="email"
                                                        value="{{ old('email') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Mobile')</label>
                                                    <input type="text" class="form-control" name="mobile"
                                                        value="{{ old('mobile') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Address')</label>
                                                    <textarea name="address" class="form-control">{{ old('address') }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Password')</label>
                                                    <input type="password" class="form-control" name="password" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Confirm Password')</label>
                                                    <input type="password" class="form-control" name="password_confirmation"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Documents Tab -->
                                    <div class="tab-pane fade" id="documents" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Passport Size Photo')</label>
                                                    <div class="image-upload">
                                                        <div class="thumb">
                                                            <div class="avatar-preview">
                                                                <div class="profilePicPreview"
                                                                    style="background-image: url({{ getImage('', imagePath()['operator']['size']) }})">
                                                                    <button type="button" class="remove-image"><i
                                                                            class="fa fa-times"></i></button>
                                                                </div>
                                                            </div>
                                                            <div class="avatar-edit">
                                                                <input type="file" class="profilePicUpload"
                                                                    name="photo" id="photoUpload"
                                                                    accept=".png, .jpg, .jpeg">
                                                                <label for="photoUpload"
                                                                    class="bg--primary">@lang('Upload Photo')</label>
                                                                <small class="mt-2 text-facebook">@lang('Supported files'):
                                                                    <b>@lang('jpeg'), @lang('jpg'),
                                                                        @lang('png')</b>. @lang('Image will be resized into')
                                                                    {{ imagePath()['operator']['size'] }}@lang('px').
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('PAN Card')</label>
                                                    <div class="image-upload">
                                                        <div class="thumb">
                                                            <div class="avatar-preview">
                                                                <div class="profilePicPreview"
                                                                    style="background-image: url({{ getImage('', imagePath()['operator']['size']) }})">
                                                                    <button type="button" class="remove-image"><i
                                                                            class="fa fa-times"></i></button>
                                                                </div>
                                                            </div>
                                                            <div class="avatar-edit">
                                                                <input type="file" class="profilePicUpload"
                                                                    name="pan_card" id="panUpload"
                                                                    accept=".png, .jpg, .jpeg">
                                                                <label for="panUpload"
                                                                    class="bg--primary">@lang('Upload PAN')</label>
                                                                <small class="mt-2 text-facebook">@lang('Supported files'):
                                                                    <b>@lang('jpeg'), @lang('jpg'),
                                                                        @lang('png')</b>.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Aadhaar Card')</label>
                                                    <div class="image-upload">
                                                        <div class="thumb">
                                                            <div class="avatar-preview">
                                                                <div class="profilePicPreview"
                                                                    style="background-image: url({{ getImage('', imagePath()['operator']['size']) }})">
                                                                    <button type="button" class="remove-image"><i
                                                                            class="fa fa-times"></i></button>
                                                                </div>
                                                            </div>
                                                            <div class="avatar-edit">
                                                                <input type="file" class="profilePicUpload"
                                                                    name="aadhaar_card" id="aadhaarUpload"
                                                                    accept=".png, .jpg, .jpeg">
                                                                <label for="aadhaarUpload"
                                                                    class="bg--primary">@lang('Upload Aadhaar')</label>
                                                                <small class="mt-2 text-facebook">@lang('Supported files'):
                                                                    <b>@lang('jpeg'), @lang('jpg'),
                                                                        @lang('png')</b>.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Driving License')</label>
                                                    <div class="image-upload">
                                                        <div class="thumb">
                                                            <div class="avatar-preview">
                                                                <div class="profilePicPreview"
                                                                    style="background-image: url({{ getImage('', imagePath()['operator']['size']) }})">
                                                                    <button type="button" class="remove-image"><i
                                                                            class="fa fa-times"></i></button>
                                                                </div>
                                                            </div>
                                                            <div class="avatar-edit">
                                                                <input type="file" class="profilePicUpload"
                                                                    name="driving_license" id="licenseUpload"
                                                                    accept=".png, .jpg, .jpeg">
                                                                <label for="licenseUpload"
                                                                    class="bg--primary">@lang('Upload License')</label>
                                                                <small class="mt-2 text-facebook">@lang('Supported files'):
                                                                    <b>@lang('jpeg'), @lang('jpg'),
                                                                        @lang('png')</b>.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Details Tab -->
                                    <div class="tab-pane fade" id="bank-details" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group"><label>@lang('Bank Name')</label><input
                                                        type="text" class="form-control" name="bank_name"
                                                        value="{{ old('bank_name') }}"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label>@lang('Account Holder Name')</label><input
                                                        type="text" class="form-control" name="account_holder_name"
                                                        value="{{ old('account_holder_name') }}"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label>@lang('Account Number')</label><input
                                                        type="text" class="form-control" name="account_number"
                                                        value="{{ old('account_number') }}"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label>@lang('IFSC Code')</label><input
                                                        type="text" class="form-control" name="ifsc_code"
                                                        value="{{ old('ifsc_code') }}"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label>@lang('GST Number')</label><input
                                                        type="text" class="form-control" name="gst_number"
                                                        value="{{ old('gst_number') }}"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>@lang('Cancelled Cheque')</label>
                                                    <div class="image-upload">
                                                        <div class="thumb">
                                                            <div class="avatar-preview">
                                                                <div class="profilePicPreview"
                                                                    style="background-image: url({{ getImage('', imagePath()['operator']['size']) }})">
                                                                    <button type="button" class="remove-image"><i
                                                                            class="fa fa-times"></i></button>
                                                                </div>
                                                            </div>
                                                            <div class="avatar-edit">
                                                                <input type="file" class="profilePicUpload"
                                                                    name="cancelled_cheque" id="chequeUpload"
                                                                    accept=".png, .jpg, .jpeg">
                                                                <label for="chequeUpload"
                                                                    class="bg--primary">@lang('Upload Cheque')</label>
                                                                <small class="mt-2 text-facebook">@lang('Supported files'):
                                                                    <b>@lang('jpeg'), @lang('jpg'),
                                                                        @lang('png')</b>.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.fleet.operators.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small"><i
            class="las la-angle-double-left"></i>@lang('Go Back')</a>
@endpush
