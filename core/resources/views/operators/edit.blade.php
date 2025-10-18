@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Edit Operator') - {{ $operator->name }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.fleet.operators.update', $operator) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Tab navigation -->
                        <ul class="nav nav-tabs" id="operatorTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-details-tab" data-toggle="tab"
                                    data-target="#basic-details" type="button" role="tab">@lang('Basic Details')</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="company-details-tab" data-toggle="tab"
                                    data-target="#company-details" type="button" role="tab">@lang('Company Details')</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="documents-tab" data-toggle="tab" data-target="#documents"
                                    type="button" role="tab">@lang('Documents')</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="bank-details-tab" data-toggle="tab" data-target="#bank-details"
                                    type="button" role="tab">@lang('Bank Details')</button>
                            </li>
                        </ul>

                        <!-- Tab content -->
                        <div class="tab-content mt-4" id="operatorTabsContent">
                            <!-- Basic Details Tab -->
                            <div class="tab-pane fade show active" id="basic-details" role="tabpanel">
                                <h5 class="mb-3">@lang('Basic Information')</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Full Name') <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                name="name" value="{{ old('name', $operator->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Email') <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                name="email" value="{{ old('email', $operator->email) }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Mobile') <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('mobile') is-invalid @enderror"
                                                name="mobile" value="{{ old('mobile', $operator->mobile) }}" required>
                                            @error('mobile')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Address')</label>
                                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $operator->address) }}</textarea>
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Password') <small>(@lang('Leave blank to keep current password'))</small></label>
                                            <input type="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                name="password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Confirm Password')</label>
                                            <input type="password"
                                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                                name="password_confirmation">
                                            @error('password_confirmation')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Details Tab -->
                            <div class="tab-pane fade" id="company-details" role="tabpanel">
                                <h5 class="mb-3">@lang('Company Information')</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Company Name')</label>
                                            <input type="text"
                                                class="form-control @error('company_name') is-invalid @enderror"
                                                name="company_name"
                                                value="{{ old('company_name', $operator->company_name) }}">
                                            @error('company_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('City')</label>
                                            <input type="text" class="form-control @error('city') is-invalid @enderror"
                                                name="city" value="{{ old('city', $operator->city) }}">
                                            @error('city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('State')</label>
                                            <input type="text"
                                                class="form-control @error('state') is-invalid @enderror" name="state"
                                                value="{{ old('state', $operator->state) }}">
                                            @error('state')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents Tab -->
                            <div class="tab-pane fade" id="documents" role="tabpanel">
                                <h5 class="mb-3">@lang('Documents')</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Passport Size Photo')</label>
                                            <div class="image-upload">
                                                <div class="thumb">
                                                    <div class="avatar-preview">
                                                        <div class="profilePicPreview"
                                                            style="background-image: url({{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->photo, imagePath()['profile']['operator']['size']) }})">
                                                            <button type="button" class="remove-image"><i
                                                                    class="fa fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="avatar-edit">
                                                        <input type="file"
                                                            class="profilePicUpload @error('photo') is-invalid @enderror"
                                                            name="photo" id="photoUpload" accept=".png, .jpg, .jpeg">
                                                        <label for="photoUpload"
                                                            class="bg--primary">@lang('Upload Photo')</label>
                                                        <small class="mt-2 text-facebook">@lang('Supported files'):
                                                            <b>@lang('jpeg'), @lang('jpg'),
                                                                @lang('png')</b>. @lang('Image will be resized into')
                                                            {{ imagePath()['profile']['operator']['size'] }}@lang('px').</small>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('photo')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('PAN Card')</label>
                                            <div class="image-upload">
                                                <div class="thumb">
                                                    <div class="avatar-preview">
                                                        <div class="profilePicPreview"
                                                            style="background-image: url({{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->pan_card, imagePath()['profile']['operator']['size']) }})">
                                                            <button type="button" class="remove-image"><i
                                                                    class="fa fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="avatar-edit">
                                                        <input type="file"
                                                            class="profilePicUpload @error('pan_card') is-invalid @enderror"
                                                            name="pan_card" id="panUpload" accept=".png, .jpg, .jpeg">
                                                        <label for="panUpload"
                                                            class="bg--primary">@lang('Upload PAN')</label>
                                                        <small class="mt-2 text-facebook">@lang('Supported files'):
                                                            <b>@lang('jpeg'), @lang('jpg'),
                                                                @lang('png')</b>.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('pan_card')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Aadhaar Card Front')</label>
                                            <div class="image-upload">
                                                <div class="thumb">
                                                    <div class="avatar-preview">
                                                        <div class="profilePicPreview"
                                                            style="background-image: url({{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->aadhaar_card_front, imagePath()['profile']['operator']['size']) }})">
                                                            <button type="button" class="remove-image"><i
                                                                    class="fa fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="avatar-edit">
                                                        <input type="file"
                                                            class="profilePicUpload @error('aadhaar_card_front') is-invalid @enderror"
                                                            name="aadhaar_card_front" id="aadhaarFrontUpload"
                                                            accept=".png, .jpg, .jpeg">
                                                        <label for="aadhaarFrontUpload"
                                                            class="bg--primary">@lang('Upload Aadhaar Front')</label>
                                                        <small class="mt-2 text-facebook">@lang('Supported files'):
                                                            <b>@lang('jpeg'), @lang('jpg'),
                                                                @lang('png')</b>.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('aadhaar_card_front')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Aadhaar Card Back')</label>
                                            <div class="image-upload">
                                                <div class="thumb">
                                                    <div class="avatar-preview">
                                                        <div class="profilePicPreview"
                                                            style="background-image: url({{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->aadhaar_card_back, imagePath()['profile']['operator']['size']) }})">
                                                            <button type="button" class="remove-image"><i
                                                                    class="fa fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="avatar-edit">
                                                        <input type="file"
                                                            class="profilePicUpload @error('aadhaar_card_back') is-invalid @enderror"
                                                            name="aadhaar_card_back" id="aadhaarBackUpload"
                                                            accept=".png, .jpg, .jpeg">
                                                        <label for="aadhaarBackUpload"
                                                            class="bg--primary">@lang('Upload Aadhaar Back')</label>
                                                        <small class="mt-2 text-facebook">@lang('Supported files'):
                                                            <b>@lang('jpeg'), @lang('jpg'),
                                                                @lang('png')</b>.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('aadhaar_card_back')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Business License')</label>
                                            <div class="image-upload">
                                                <div class="thumb">
                                                    <div class="avatar-preview">
                                                        <div class="profilePicPreview"
                                                            style="background-image: url({{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->business_license, imagePath()['profile']['operator']['size']) }})">
                                                            <button type="button" class="remove-image"><i
                                                                    class="fa fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="avatar-edit">
                                                        <input type="file"
                                                            class="profilePicUpload @error('business_license') is-invalid @enderror"
                                                            name="business_license" id="businessLicenseUpload"
                                                            accept=".png, .jpg, .jpeg, .pdf">
                                                        <label for="businessLicenseUpload"
                                                            class="bg--primary">@lang('Upload Business License')</label>
                                                        <small class="mt-2 text-facebook">@lang('Supported files'):
                                                            <b>@lang('jpeg'), @lang('jpg'), @lang('png'),
                                                                @lang('pdf')</b>.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('business_license')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Details Tab -->
                            <div class="tab-pane fade" id="bank-details" role="tabpanel">
                                <h5 class="mb-3">@lang('Banking Information')</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Bank Name')</label>
                                            <input type="text"
                                                class="form-control @error('bank_name') is-invalid @enderror"
                                                name="bank_name" value="{{ old('bank_name', $operator->bank_name) }}">
                                            @error('bank_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Account Holder Name')</label>
                                            <input type="text"
                                                class="form-control @error('account_holder_name') is-invalid @enderror"
                                                name="account_holder_name"
                                                value="{{ old('account_holder_name', $operator->account_holder_name) }}">
                                            @error('account_holder_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Account Number')</label>
                                            <input type="text"
                                                class="form-control @error('account_number') is-invalid @enderror"
                                                name="account_number"
                                                value="{{ old('account_number', $operator->account_number) }}">
                                            @error('account_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('IFSC Code')</label>
                                            <input type="text"
                                                class="form-control @error('ifsc_code') is-invalid @enderror"
                                                name="ifsc_code" value="{{ old('ifsc_code', $operator->ifsc_code) }}">
                                            @error('ifsc_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('GST Number')</label>
                                            <input type="text"
                                                class="form-control @error('gst_number') is-invalid @enderror"
                                                name="gst_number" value="{{ old('gst_number', $operator->gst_number) }}">
                                            @error('gst_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Cancelled Cheque')</label>
                                            <div class="image-upload">
                                                <div class="thumb">
                                                    <div class="avatar-preview">
                                                        <div class="profilePicPreview"
                                                            style="background-image: url({{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->cancelled_cheque, imagePath()['profile']['operator']['size']) }})">
                                                            <button type="button" class="remove-image"><i
                                                                    class="fa fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="avatar-edit">
                                                        <input type="file"
                                                            class="profilePicUpload @error('cancelled_cheque') is-invalid @enderror"
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
                                            @error('cancelled_cheque')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn--primary w-100">@lang('Update Operator')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.fleet.operators.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
