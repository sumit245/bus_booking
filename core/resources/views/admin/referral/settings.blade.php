@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="row justify-content-center mt-4">
                        <div class="col-lg-10">
                            <div class="card border--primary">
                                <div class="card-header bg--primary">
                                    <h5 class="card-title text-white">@lang('Referral Program Settings')</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('admin.referral.settings.update') }}" method="POST">
                                        @csrf

                                        <!-- Enable/Disable -->
                                        <div class="row mb-4">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label>@lang('Enable Referral Program')</label>
                                                    <input type="checkbox" data-width="100%" data-height="50"
                                                        data-onstyle="-success" data-offstyle="-danger" data-toggle="toggle"
                                                        data-on="@lang('Enabled')" data-off="@lang('Disabled')"
                                                        name="is_enabled" {{ $settings->is_enabled ? 'checked' : '' }}
                                                        value="1">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label>@lang('Use Point System')</label>
                                                    <input type="checkbox" data-width="100%" data-height="50"
                                                        data-onstyle="-success" data-offstyle="-danger" data-toggle="toggle"
                                                        data-on="@lang('Points')" data-off="@lang('Currency')"
                                                        name="use_point_system" id="usePointSystem"
                                                        {{ $settings->use_point_system ? 'checked' : '' }} value="1">
                                                    <small class="text-muted">@lang('Reward users with points instead of real money')</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Point Conversion -->
                                        <div class="row mb-4" id="pointConversionDiv"
                                            style="{{ $settings->use_point_system ? '' : 'display:none;' }}">
                                            <div class="col-lg-12">
                                                <div class="alert border border--info">
                                                    <div class="form-group mb-0">
                                                        <label>@lang('Point Conversion Rate')</label>
                                                        <div class="input-group">
                                                            <input type="number" name="points_per_currency"
                                                                class="form-control"
                                                                value="{{ $settings->points_per_currency ?? 1 }}"
                                                                min="1">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">@lang('Points') = 1
                                                                    {{ __($general->cur_text) }}</span>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">@lang('Example: If set to 10, then 10 points = 1')
                                                            {{ __($general->cur_text) }}. @lang('Users earn points, which can later be converted to')
                                                            {{ __($general->cur_text) }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reward Configuration -->
                                        <div class="card border mb-4">
                                            <div class="card-header bg--dark">
                                                <h6 class="text-white mb-0">@lang('Reward Configuration')</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label>@lang('Reward Type')</label>
                                                    <select name="reward_type" class="form-control" id="rewardType">
                                                        <option value="fixed"
                                                            {{ $settings->reward_type == 'fixed' ? 'selected' : '' }}>
                                                            @lang('Fixed Amount')</option>
                                                        <option value="percent"
                                                            {{ $settings->reward_type == 'percent' ? 'selected' : '' }}>
                                                            @lang('Percentage of Base Amount')</option>
                                                        <option value="percent_of_ticket"
                                                            {{ $settings->reward_type == 'percent_of_ticket' ? 'selected' : '' }}>
                                                            @lang('Percentage of Ticket Amount')</option>
                                                    </select>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group" id="fixedAmountDiv">
                                                            <label>@lang('Fixed Amount')
                                                                ({{ __($general->cur_text) }})</label>
                                                            <input type="number" step="0.01" name="fixed_amount"
                                                                class="form-control"
                                                                value="{{ getAmount($settings->fixed_amount) }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group" id="percentShareDiv">
                                                            <label>@lang('Percentage Share') (%)</label>
                                                            <input type="number" step="0.01" name="percent_share"
                                                                class="form-control"
                                                                value="{{ getAmount($settings->percent_share) }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group" id="percentOfTicketDiv">
                                                            <label>@lang('Percentage of Ticket') (%)</label>
                                                            <input type="number" step="0.01" name="percent_of_ticket"
                                                                class="form-control"
                                                                value="{{ getAmount($settings->percent_of_ticket) }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Event Triggers -->
                                        <div class="card border mb-4">
                                            <div class="card-header bg--dark">
                                                <h6 class="text-white mb-0">@lang('When to Reward')</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>@lang('On App Install')</label>
                                                            <input type="checkbox" data-width="100%" data-height="40"
                                                                data-onstyle="-success" data-offstyle="-danger"
                                                                data-toggle="toggle" data-on="@lang('Yes')"
                                                                data-off="@lang('No')" name="reward_on_install"
                                                                {{ $settings->reward_on_install ? 'checked' : '' }}
                                                                value="1">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>@lang('On Signup')</label>
                                                            <input type="checkbox" data-width="100%" data-height="40"
                                                                data-onstyle="-success" data-offstyle="-danger"
                                                                data-toggle="toggle" data-on="@lang('Yes')"
                                                                data-off="@lang('No')" name="reward_on_signup"
                                                                {{ $settings->reward_on_signup ? 'checked' : '' }}
                                                                value="1">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>@lang('On First Booking')</label>
                                                            <input type="checkbox" data-width="100%" data-height="40"
                                                                data-onstyle="-success" data-offstyle="-danger"
                                                                data-toggle="toggle" data-on="@lang('Yes')"
                                                                data-off="@lang('No')"
                                                                name="reward_on_first_booking"
                                                                {{ $settings->reward_on_first_booking ? 'checked' : '' }}
                                                                value="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Beneficiaries -->
                                        <div class="card border mb-4">
                                            <div class="card-header bg--dark">
                                                <h6 class="text-white mb-0">@lang('Who Gets Rewarded')</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>@lang('Reward Referrer (Person who shared)')</label>
                                                            <input type="checkbox" data-width="100%" data-height="40"
                                                                data-onstyle="-success" data-offstyle="-danger"
                                                                data-toggle="toggle" data-on="@lang('Yes')"
                                                                data-off="@lang('No')" name="reward_referrer"
                                                                {{ $settings->reward_referrer ? 'checked' : '' }}
                                                                value="1">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>@lang('Reward Referee (Person who signed up)')</label>
                                                            <input type="checkbox" data-width="100%" data-height="40"
                                                                data-onstyle="-success" data-offstyle="-danger"
                                                                data-toggle="toggle" data-on="@lang('Yes')"
                                                                data-off="@lang('No')" name="reward_referee"
                                                                {{ $settings->reward_referee ? 'checked' : '' }}
                                                                value="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Limits and Constraints -->
                                        <div class="card border mb-4">
                                            <div class="card-header bg--dark">
                                                <h6 class="text-white mb-0">@lang('Limits & Constraints')</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>@lang('Minimum Booking Amount')
                                                                ({{ __($general->cur_text) }})</label>
                                                            <input type="number" step="0.01"
                                                                name="min_booking_amount" class="form-control"
                                                                value="{{ getAmount($settings->min_booking_amount) }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>@lang('Reward Credit Days')</label>
                                                            <input type="number" name="reward_credit_days"
                                                                class="form-control"
                                                                value="{{ $settings->reward_credit_days }}">
                                                            <small class="text-muted">@lang('Days to wait before crediting reward (0 = immediate)')</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>@lang('Daily Referral Cap')</label>
                                                            <input type="number" name="daily_cap_per_referrer"
                                                                class="form-control"
                                                                value="{{ $settings->daily_cap_per_referrer }}">
                                                            <small class="text-muted">@lang('Max referrals per day per user (blank = unlimited)')</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label>@lang('Lifetime Max Referrals')</label>
                                                            <input type="number" name="max_referrals_per_user"
                                                                class="form-control"
                                                                value="{{ $settings->max_referrals_per_user }}">
                                                            <small class="text-muted">@lang('Maximum total referrals per user (blank = unlimited)')</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Share Message -->
                                        <div class="form-group">
                                            <label>@lang('Share Message')</label>
                                            <textarea name="share_message" class="form-control" rows="3">{{ $settings->share_message }}</textarea>
                                        </div>

                                        <!-- Terms and Conditions -->
                                        <div class="form-group">
                                            <label>@lang('Terms and Conditions')</label>
                                            <textarea name="terms_and_conditions" class="form-control" rows="8"
                                                placeholder="Example:
1. Referral rewards are only valid for new users who have never registered before.
2. Self-referrals are not allowed and will be automatically blocked.
3. Rewards will be credited within {{ $settings->reward_credit_days }} days after the referred user completes their first booking.
4. The minimum booking amount for reward eligibility is {{ showAmount($settings->min_booking_amount) }} {{ __($general->cur_text) }}.
5. Referral codes can be used only once per new user.
6. Management reserves the right to cancel rewards in case of fraudulent activity.
7. Rewards are non-transferable and cannot be exchanged for cash.
8. Terms and conditions are subject to change without prior notice.">{{ $settings->terms_and_conditions }}</textarea>
                                            <small class="text-muted">@lang('These terms will be shown to users. Customize as needed.')</small>
                                        </div>

                                        <!-- Notes -->
                                        <div class="form-group">
                                            <label>@lang('Admin Notes')</label>
                                            <textarea name="notes" class="form-control" rows="3">{{ $settings->notes }}</textarea>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit"
                                                class="btn btn--primary btn-block btn-lg">@lang('Update Settings')</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";

            // Show/hide relevant fields based on reward type
            function toggleRewardFields() {
                var type = $('#rewardType').val();
                $('#fixedAmountDiv, #percentShareDiv, #percentOfTicketDiv').hide();

                if (type === 'fixed') {
                    $('#fixedAmountDiv').show();
                } else if (type === 'percent') {
                    $('#percentShareDiv').show();
                } else if (type === 'percent_of_ticket') {
                    $('#percentOfTicketDiv').show();
                }
            }

            // Show/hide point conversion field
            function togglePointConversion() {
                if ($('#usePointSystem').is(':checked')) {
                    $('#pointConversionDiv').slideDown();
                } else {
                    $('#pointConversionDiv').slideUp();
                }
            }

            $('#rewardType').on('change', toggleRewardFields);
            toggleRewardFields();

            $('#usePointSystem').on('change', togglePointConversion);
            togglePointConversion();

        })(jQuery);
    </script>
@endpush
