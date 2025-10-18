@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">@lang('Operator Details') - {{ $operator->name }}</h4>
                </div>
                <div class="card-body">
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
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>@lang('Full Name')</label>
                                        <p>{{ $operator->name }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>@lang('Email')</label>
                                        <p>{{ $operator->email }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>@lang('Mobile')</label>
                                        <p>{{ $operator->mobile }}</p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-item">
                                        <label>@lang('Address')</label>
                                        <p>{{ $operator->address }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>@lang('Status')</label>
                                        <p>
                                            @if ($operator->status)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Inactive')</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>@lang('Registration Date')</label>
                                        <p>{{ showDateTime($operator->created_at, 'd M, Y') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>@lang('Last Updated')</label>
                                        <p>{{ showDateTime($operator->updated_at, 'd M, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Company Details Tab -->
                        <div class="tab-pane fade" id="company-details" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Company Name')</label>
                                        <p>{{ $operator->company_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <label>@lang('City')</label>
                                        <p>{{ $operator->city ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <label>@lang('State')</label>
                                        <p>{{ $operator->state ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Passport Size Photo')</label>
                                        @if ($operator->photo)
                                            <div class="document-preview">
                                                <img src="{{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->photo, imagePath()['profile']['operator']['size']) }}"
                                                    alt="Photo" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No photo uploaded')</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('PAN Card')</label>
                                        @if ($operator->pan_card)
                                            <div class="document-preview">
                                                <img src="{{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->pan_card, imagePath()['profile']['operator']['size']) }}"
                                                    alt="PAN Card" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No PAN card uploaded')</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Aadhaar Card Front')</label>
                                        @if ($operator->aadhaar_card_front)
                                            <div class="document-preview">
                                                <img src="{{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->aadhaar_card_front, imagePath()['profile']['operator']['size']) }}"
                                                    alt="Aadhaar Front" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No Aadhaar front uploaded')</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Aadhaar Card Back')</label>
                                        @if ($operator->aadhaar_card_back)
                                            <div class="document-preview">
                                                <img src="{{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->aadhaar_card_back, imagePath()['profile']['operator']['size']) }}"
                                                    alt="Aadhaar Back" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No Aadhaar back uploaded')</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Business License')</label>
                                        @if ($operator->business_license)
                                            <div class="document-preview">
                                                @if (str_ends_with($operator->business_license, '.pdf'))
                                                    <a href="{{ asset('storage/app/public/' . imagePath()['profile']['operator']['path'] . '/' . $operator->business_license) }}"
                                                        target="_blank"
                                                        class="btn btn--primary btn-sm">@lang('View PDF')</a>
                                                @else
                                                    <img src="{{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->business_license, imagePath()['profile']['operator']['size']) }}"
                                                        alt="Business License" class="img-thumbnail"
                                                        style="max-width: 200px;">
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No business license uploaded')</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Cancelled Cheque')</label>
                                        @if ($operator->cancelled_cheque)
                                            <div class="document-preview">
                                                <img src="{{ getImage(imagePath()['profile']['operator']['path'] . '/' . $operator->cancelled_cheque, imagePath()['profile']['operator']['size']) }}"
                                                    alt="Cancelled Cheque" class="img-thumbnail"
                                                    style="max-width: 200px;">
                                            </div>
                                        @else
                                            <p class="text-muted">@lang('No cancelled cheque uploaded')</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Details Tab -->
                        <div class="tab-pane fade" id="bank-details" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Bank Name')</label>
                                        <p>{{ $operator->bank_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Account Holder Name')</label>
                                        <p>{{ $operator->account_holder_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('Account Number')</label>
                                        <p>{{ $operator->account_number ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('IFSC Code')</label>
                                        <p>{{ $operator->ifsc_code ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>@lang('GST Number')</label>
                                        <p>{{ $operator->gst_number ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-center">
                        <a href="{{ route('admin.fleet.operators.edit', $operator) }}" class="btn btn--primary">
                            <i class="la la-pen"></i> @lang('Edit Operator')
                        </a>
                        <a href="{{ route('admin.fleet.operators.index') }}" class="btn btn--secondary">
                            <i class="la la-angle-double-left"></i> @lang('Back to List')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .info-item {
            margin-bottom: 20px;
        }

        .info-item label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            display: block;
        }

        .info-item p {
            margin: 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .document-preview {
            margin-top: 10px;
        }

        .document-preview img {
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>
@endpush

@push('breadcrumb-plugins')
    <a href="{{ route('admin.fleet.operators.index') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="las la-angle-double-left"></i>@lang('Go Back')
    </a>
@endpush
