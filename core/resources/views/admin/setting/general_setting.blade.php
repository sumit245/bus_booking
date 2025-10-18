@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group ">
                                    <label class="form-control-label font-weight-bold"> @lang('Site Title') </label>
                                    <input class="form-control form-control-lg" type="text" name="sitename"
                                        value="{{ $general->sitename }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group ">
                                    <label class="form-control-label font-weight-bold">@lang('Currency')</label>
                                    <input class="form-control form-control-lg" type="text" name="cur_text"
                                        value="{{ $general->cur_text }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group ">
                                    <label class="form-control-label font-weight-bold">@lang('Currency Symbol') </label>
                                    <input class="form-control form-control-lg" type="text" name="cur_sym"
                                        value="{{ $general->cur_sym }}">
                                </div>
                            </div>

                            <div class="form-group col-md-3">
                                <label class="form-control-label font-weight-bold"> @lang('Timezone')</label>
                                <select class="select2-basic" name="timezone">
                                    @foreach ($timezones as $timezone)
                                        <option value="'{{ @$timezone }}'"
                                            @if (config('app.timezone') == $timezone) selected @endif>{{ __($timezone) }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="form-control-label font-weight-bold"> @lang('Site Base Color')</label>
                                <div class="input-group">
                                    <span class="input-group-addon ">
                                        <input type='text' class="form-control form-control-lg colorPicker"
                                            value="{{ $general->base_color }}" />
                                    </span>
                                    <input type="text" class="form-control form-control-lg colorCode" name="base_color"
                                        value="{{ $general->base_color }}" />
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-control-label font-weight-bold">@lang('Force Secure Password')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disabled')" name="secure_password"
                                    @if ($general->secure_password) checked @endif>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="form-control-label font-weight-bold">@lang('Agree policy')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disabled')" name="agree"
                                    @if ($general->agree) checked @endif>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-2">
                                <label class="form-control-label font-weight-bold">@lang('User Registration')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disabled')" name="registration"
                                    @if ($general->registration) checked @endif>
                            </div>

                            <div class="form-group col-md-2">
                                <label class="form-control-label font-weight-bold">@lang('Force SSL')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disabled')" name="force_ssl"
                                    @if ($general->force_ssl) checked @endif>
                            </div>
                            <div class="form-group col-lg-2 col-sm-6 col-md-4">
                                <label class="form-control-label font-weight-bold"> @lang('Email Verification')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disable')" name="ev"
                                    @if ($general->ev) checked @endif>
                            </div>
                            <div class="form-group col-lg-2 col-sm-6 col-md-4">
                                <label class="form-control-label font-weight-bold">@lang('Email Notification')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disable')" name="en"
                                    @if ($general->en) checked @endif>
                            </div>
                            <div class="form-group col-lg-2 col-sm-6 col-md-4">
                                <label class="form-control-label font-weight-bold"> @lang('SMS Verification')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disable')" name="sv"
                                    @if ($general->sv) checked @endif>
                            </div>
                            <div class="form-group col-lg-2 col-sm-6 col-md-4">
                                <label class="form-control-label font-weight-bold">@lang('SMS Notification')</label>
                                <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success"
                                    data-offstyle="-danger" data-toggle="toggle" data-on="@lang('Enable')"
                                    data-off="@lang('Disable')" name="sn"
                                    @if ($general->sn) checked @endif>
                            </div>
                        </div>

                        <!-- Fee Settings Section -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3 mt-4">@lang('Fee Settings')</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label font-weight-bold">@lang('GST Percentage')</label>
                                    <div class="input-group">
                                        <input class="form-control form-control-lg" type="number" name="gst_percentage"
                                            value="{{ $general->gst_percentage ?? 0 }}" min="0" max="100"
                                            step="0.01" placeholder="0.00">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">@lang('GST percentage applied on total amount (including service charge and platform fee)')</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label font-weight-bold">@lang('Service Charge Percentage')</label>
                                    <div class="input-group">
                                        <input class="form-control form-control-lg" type="number"
                                            name="service_charge_percentage"
                                            value="{{ $general->service_charge_percentage ?? 0 }}" min="0"
                                            max="100" step="0.01" placeholder="0.00">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">@lang('Service charge percentage applied on base fare')</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label font-weight-bold">@lang('Platform Fee Percentage')</label>
                                    <div class="input-group">
                                        <input class="form-control form-control-lg" type="number"
                                            name="platform_fee_percentage"
                                            value="{{ $general->platform_fee_percentage ?? 0 }}" min="0"
                                            max="100" step="0.01" placeholder="0.00">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">@lang('Platform fee percentage applied on base fare')</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-control-label font-weight-bold">@lang('Fixed Platform Fee')</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $general->cur_sym ?? '₹' }}</span>
                                        </div>
                                        <input class="form-control form-control-lg" type="number"
                                            name="platform_fee_fixed" value="{{ $general->platform_fee_fixed ?? 0 }}"
                                            min="0" step="0.01" placeholder="0.00">
                                    </div>
                                    <small class="text-muted">@lang('Fixed platform fee amount added to each booking')</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="las la-info-circle"></i> @lang('Fee Calculation Order')</h6>
                                    <ol class="mb-0">
                                        <li>@lang('Base fare from seat selection')</li>
                                        <li>@lang('Add Service Charge (percentage of base fare)')</li>
                                        <li>@lang('Add Platform Fee (percentage of base fare + fixed amount)')</li>
                                        <li>@lang('Apply GST (percentage of total amount before GST)')</li>
                                        <li>@lang('Apply coupon discount (if applicable)')</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Agent Commission Configuration Section -->
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <h5 class="mb-3"><i class="las la-percentage"></i> @lang('Agent Commission Configuration')</h5>
                                <p class="text-muted">@lang('Configure commission structure for agents based on booking amounts')</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label font-weight-bold">@lang('Threshold Amount')</label>
                                    <input class="form-control form-control-lg" type="number"
                                        name="agent_commission_config[threshold_amount]"
                                        value="{{ $general->agent_commission_config['threshold_amount'] ?? 500 }}"
                                        min="0" step="0.01" required>
                                    <small class="form-text text-muted">@lang('Amount below which fixed commission applies')</small>
                                </div>
                            </div>
                        </div>

                        <!-- Below Threshold Rules -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-3">@lang('Below Threshold Rules (Fixed Commission)')</h6>
                                <div id="below-threshold-rules">
                                    @if (isset($general->agent_commission_config['below_threshold']))
                                        @foreach ($general->agent_commission_config['below_threshold'] as $index => $rule)
                                            <div class="row below-threshold-rule mb-3" data-index="{{ $index }}">
                                                <div class="col-md-4">
                                                    <input class="form-control" type="text"
                                                        name="agent_commission_config[below_threshold][{{ $index }}][condition]"
                                                        value="{{ $rule['condition'] ?? '' }}"
                                                        placeholder="e.g., 0-200, 200-500" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input class="form-control" type="number"
                                                        name="agent_commission_config[below_threshold][{{ $index }}][amount]"
                                                        value="{{ $rule['amount'] ?? '' }}" min="0"
                                                        step="0.01" placeholder="Fixed Amount" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-danger remove-below-rule">
                                                        <i class="las la-trash"></i> @lang('Remove')
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="row below-threshold-rule mb-3" data-index="0">
                                            <div class="col-md-4">
                                                <input class="form-control" type="text"
                                                    name="agent_commission_config[below_threshold][0][condition]"
                                                    value="0-200" placeholder="e.g., 0-200, 200-500" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="number"
                                                    name="agent_commission_config[below_threshold][0][amount]"
                                                    value="50" min="0" step="0.01"
                                                    placeholder="Fixed Amount" required>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-danger remove-below-rule">
                                                    <i class="las la-trash"></i> @lang('Remove')
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-info add-below-rule">
                                    <i class="las la-plus"></i> @lang('Add Below Threshold Rule')
                                </button>
                            </div>
                        </div>

                        <!-- Above Threshold Rules -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-3">@lang('Above Threshold Rules (Percentage Commission)')</h6>
                                <div id="above-threshold-rules">
                                    @if (isset($general->agent_commission_config['above_threshold']))
                                        @foreach ($general->agent_commission_config['above_threshold'] as $index => $rule)
                                            <div class="row above-threshold-rule mb-3" data-index="{{ $index }}">
                                                <div class="col-md-4">
                                                    <input class="form-control" type="text"
                                                        name="agent_commission_config[above_threshold][{{ $index }}][condition]"
                                                        value="{{ $rule['condition'] ?? '' }}"
                                                        placeholder="e.g., 500-1000, 1000+" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input class="form-control" type="number"
                                                        name="agent_commission_config[above_threshold][{{ $index }}][percentage]"
                                                        value="{{ $rule['percentage'] ?? '' }}" min="0"
                                                        max="100" step="0.01" placeholder="Percentage" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-danger remove-above-rule">
                                                        <i class="las la-trash"></i> @lang('Remove')
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="row above-threshold-rule mb-3" data-index="0">
                                            <div class="col-md-4">
                                                <input class="form-control" type="text"
                                                    name="agent_commission_config[above_threshold][0][condition]"
                                                    value="500+" placeholder="e.g., 500-1000, 1000+" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="number"
                                                    name="agent_commission_config[above_threshold][0][percentage]"
                                                    value="5" min="0" max="100" step="0.01"
                                                    placeholder="Percentage" required>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-danger remove-above-rule">
                                                    <i class="las la-trash"></i> @lang('Remove')
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-info add-above-rule">
                                    <i class="las la-plus"></i> @lang('Add Above Threshold Rule')
                                </button>
                            </div>
                        </div>

                        <!-- Commission Preview -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="las la-info-circle"></i> @lang('Commission Calculation Example')</h6>
                                    <div id="commission-preview">
                                        <p class="mb-0">@lang('Enter amounts above to see commission calculations')</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn--primary btn-block btn-lg">@lang('Update')</button>
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
            let belowRuleIndex =
                {{ isset($general->agent_commission_config['below_threshold']) ? count($general->agent_commission_config['below_threshold']) : 1 }};
            let aboveRuleIndex =
                {{ isset($general->agent_commission_config['above_threshold']) ? count($general->agent_commission_config['above_threshold']) : 1 }};

            // Add Below Threshold Rule
            $('.add-below-rule').click(function() {
                let html = `
            <div class="row below-threshold-rule mb-3" data-index="${belowRuleIndex}">
                <div class="col-md-4">
                    <input class="form-control" type="text" 
                        name="agent_commission_config[below_threshold][${belowRuleIndex}][condition]"
                        placeholder="e.g., 0-200, 200-500" required>
                </div>
                <div class="col-md-4">
                    <input class="form-control" type="number" 
                        name="agent_commission_config[below_threshold][${belowRuleIndex}][amount]"
                        min="0" step="0.01" placeholder="Fixed Amount" required>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger remove-below-rule">
                        <i class="las la-trash"></i> @lang('Remove')
                    </button>
                </div>
            </div>
        `;
                $('#below-threshold-rules').append(html);
                belowRuleIndex++;
            });

            // Add Above Threshold Rule
            $('.add-above-rule').click(function() {
                let html = `
            <div class="row above-threshold-rule mb-3" data-index="${aboveRuleIndex}">
                <div class="col-md-4">
                    <input class="form-control" type="text" 
                        name="agent_commission_config[above_threshold][${aboveRuleIndex}][condition]"
                        placeholder="e.g., 500-1000, 1000+" required>
                </div>
                <div class="col-md-4">
                    <input class="form-control" type="number" 
                        name="agent_commission_config[above_threshold][${aboveRuleIndex}][percentage]"
                        min="0" max="100" step="0.01" placeholder="Percentage" required>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger remove-above-rule">
                        <i class="las la-trash"></i> @lang('Remove')
                    </button>
                </div>
            </div>
        `;
                $('#above-threshold-rules').append(html);
                aboveRuleIndex++;
            });

            // Remove Below Threshold Rule
            $(document).on('click', '.remove-below-rule', function() {
                if ($('.below-threshold-rule').length > 1) {
                    $(this).closest('.below-threshold-rule').remove();
                } else {
                    alert('@lang('At least one below threshold rule is required')');
                }
            });

            // Remove Above Threshold Rule
            $(document).on('click', '.remove-above-rule', function() {
                if ($('.above-threshold-rule').length > 1) {
                    $(this).closest('.above-threshold-rule').remove();
                } else {
                    alert('@lang('At least one above threshold rule is required')');
                }
            });

            // Commission Preview
            function updateCommissionPreview() {
                const threshold = parseFloat($('input[name="agent_commission_config[threshold_amount]"]').val()) ||
                    500;
                const belowRules = [];
                const aboveRules = [];

                $('.below-threshold-rule').each(function() {
                    const condition = $(this).find('input[name*="[condition]"]').val();
                    const amount = parseFloat($(this).find('input[name*="[amount]"]').val()) || 0;
                    if (condition && amount > 0) {
                        belowRules.push({
                            condition,
                            amount
                        });
                    }
                });

                $('.above-threshold-rule').each(function() {
                    const condition = $(this).find('input[name*="[condition]"]').val();
                    const percentage = parseFloat($(this).find('input[name*="[percentage]"]').val()) || 0;
                    if (condition && percentage > 0) {
                        aboveRules.push({
                            condition,
                            percentage
                        });
                    }
                });

                let previewHtml = '<div class="row">';

                // Test examples
                const testAmounts = [150, 350, 750, 1200];
                testAmounts.forEach(amount => {
                    let commission = 0;
                    let type = '';

                    if (amount <= threshold) {
                        // Below threshold - find matching rule
                        for (let rule of belowRules) {
                            if (matchesCondition(amount, rule.condition)) {
                                commission = rule.amount;
                                type = 'Fixed';
                                break;
                            }
                        }
                    } else {
                        // Above threshold - find matching rule
                        for (let rule of aboveRules) {
                            if (matchesCondition(amount, rule.condition)) {
                                commission = (amount * rule.percentage) / 100;
                                type = rule.percentage + '%';
                                break;
                            }
                        }
                    }

                    previewHtml += `
                <div class="col-md-3 mb-2">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6>₹${amount}</h6>
                            <small class="text-muted">Commission: ₹${commission.toFixed(2)} (${type})</small>
                        </div>
                    </div>
                </div>
            `;
                });

                previewHtml += '</div>';
                $('#commission-preview').html(previewHtml);
            }

            function matchesCondition(amount, condition) {
                if (condition.includes('+')) {
                    const minAmount = parseInt(condition.replace('+', ''));
                    return amount >= minAmount;
                } else if (condition.includes('-')) {
                    const [min, max] = condition.split('-').map(x => parseInt(x));
                    return amount >= min && amount <= max;
                }
                return false;
            }

            // Update preview on input change
            $(document).on('input', 'input[name*="agent_commission_config"]', function() {
                setTimeout(updateCommissionPreview, 100);
            });

            // Initial preview
            updateCommissionPreview();
        });
    </script>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/spectrum.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/spectrum.css') }}">
@endpush


@push('style')
    <style>
        .sp-replacer {
            padding: 0;
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: 5px 0 0 5px;
            border-right: none;
        }

        .sp-preview {
            width: 100px;
            height: 46px;
            border: 0;
        }

        .sp-preview-inner {
            width: 110px;
        }

        .sp-dd {
            display: none;
        }

        .select2-container .select2-selection--single {
            height: 44px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 43px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            $('.colorPicker').spectrum({
                color: $(this).data('color'),
                change: function(color) {
                    $(this).parent().siblings('.colorCode').val(color.toHexString().replace(/^#?/, ''));
                }
            });

            $('.colorCode').on('input', function() {
                var clr = $(this).val();
                $(this).parents('.input-group').find('.colorPicker').spectrum({
                    color: clr,
                });
            });

            $('.select2-basic').select2({
                dropdownParent: $('.card-body')
            });

            $('select[name=timezone]').val();
        })(jQuery);
    </script>
@endpush
