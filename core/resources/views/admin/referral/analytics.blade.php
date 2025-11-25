@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <!-- Total Codes -->
        <div class="col-xl-3 col-lg-4 col-sm-6 mb-30">
            <div class="dashboard-w1 bg--primary b-radius--10 box-shadow">
                <div class="icon">
                    <i class="las la-qrcode"></i>
                </div>
                <div class="details">
                    <div class="numbers">
                        <span class="amount">{{ $totalCodes }}</span>
                    </div>
                    <div class="desciption">
                        <span class="text--small">@lang('Active Referral Codes')</span>
                    </div>
                    <a href="{{ route('admin.referral.codes') }}"
                        class="btn btn-sm text--small bg--white text--black box--shadow3 mt-3">@lang('View All')</a>
                </div>
            </div>
        </div>

        <!-- Total Clicks -->
        <div class="col-xl-3 col-lg-4 col-sm-6 mb-30">
            <div class="dashboard-w1 bg--info b-radius--10 box-shadow">
                <div class="icon">
                    <i class="las la-mouse-pointer"></i>
                </div>
                <div class="details">
                    <div class="numbers">
                        <span class="amount">{{ $totalClicks }}</span>
                    </div>
                    <div class="desciption">
                        <span class="text--small">@lang('Total Clicks')</span>
                    </div>
                    <a href="#"
                        class="btn btn-sm text--small bg--white text--black box--shadow3 mt-3">{{ $clickToInstall }}%
                        @lang('to Install')</a>
                </div>
            </div>
        </div>

        <!-- Total Installs -->
        <div class="col-xl-3 col-lg-4 col-sm-6 mb-30">
            <div class="dashboard-w1 bg--cyan b-radius--10 box-shadow">
                <div class="icon">
                    <i class="las la-download"></i>
                </div>
                <div class="details">
                    <div class="numbers">
                        <span class="amount">{{ $totalInstalls }}</span>
                    </div>
                    <div class="desciption">
                        <span class="text--small">@lang('App Installs')</span>
                    </div>
                    <a href="#"
                        class="btn btn-sm text--small bg--white text--black box--shadow3 mt-3">{{ $installToSignup }}%
                        @lang('to Signup')</a>
                </div>
            </div>
        </div>

        <!-- Total Signups -->
        <div class="col-xl-3 col-lg-4 col-sm-6 mb-30">
            <div class="dashboard-w1 bg--success b-radius--10 box-shadow">
                <div class="icon">
                    <i class="las la-user-plus"></i>
                </div>
                <div class="details">
                    <div class="numbers">
                        <span class="amount">{{ $totalSignups }}</span>
                    </div>
                    <div class="desciption">
                        <span class="text--small">@lang('Total Signups')</span>
                    </div>
                    <a href="#"
                        class="btn btn-sm text--small bg--white text--black box--shadow3 mt-3">{{ $signupToBooking }}%
                        @lang('to Booking')</a>
                </div>
            </div>
        </div>

        <!-- Total Bookings -->
        <div class="col-xl-3 col-lg-4 col-sm-6 mb-30">
            <div class="dashboard-w1 bg--warning b-radius--10 box-shadow">
                <div class="icon">
                    <i class="las la-ticket-alt"></i>
                </div>
                <div class="details">
                    <div class="numbers">
                        <span class="amount">{{ $totalBookings }}</span>
                    </div>
                    <div class="desciption">
                        <span class="text--small">@lang('First Bookings')</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Rewards -->
        <div class="col-xl-3 col-lg-4 col-sm-6 mb-30">
            <div class="dashboard-w1 bg--1 b-radius--10 box-shadow">
                <div class="icon">
                    <i class="las la-gift"></i>
                </div>
                <div class="details">
                    <div class="numbers">
                        <span class="amount">{{ showAmount($totalRewards) }} {{ __($general->cur_text) }}</span>
                    </div>
                    <div class="desciption">
                        <span class="text--small">@lang('Total Rewards Paid')</span>
                    </div>
                    <a href="{{ route('admin.referral.rewards') }}?status=confirmed"
                        class="btn btn-sm text--small bg--white text--black box--shadow3 mt-3">@lang('View All')</a>
                </div>
            </div>
        </div>

        <!-- Pending Rewards -->
        <div class="col-xl-3 col-lg-4 col-sm-6 mb-30">
            <div class="dashboard-w1 bg--orange b-radius--10 box-shadow">
                <div class="icon">
                    <i class="las la-clock"></i>
                </div>
                <div class="details">
                    <div class="numbers">
                        <span class="amount">{{ showAmount($pendingRewards) }} {{ __($general->cur_text) }}</span>
                    </div>
                    <div class="desciption">
                        <span class="text--small">@lang('Pending Rewards')</span>
                    </div>
                    <a href="{{ route('admin.referral.rewards') }}?status=pending"
                        class="btn btn-sm text--small bg--white text--black box--shadow3 mt-3">@lang('View All')</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Referrers -->
    <div class="row mt-50">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Top Referrers')</th>
                                    <th>@lang('Code')</th>
                                    <th>@lang('Clicks')</th>
                                    <th>@lang('Installs')</th>
                                    <th>@lang('Signups')</th>
                                    <th>@lang('Bookings')</th>
                                    <th>@lang('Earnings')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topReferrers as $code)
                                    <tr>
                                        <td data-label="@lang('User')">
                                            @if ($code->user)
                                                <a href="{{ route('admin.users.detail', $code->user_id) }}">
                                                    {{ $code->user->fullname }}
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $code->user->mobile }}</small>
                                            @else
                                                <span class="text-muted">@lang('Not linked')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Code')">
                                            <span class="font-weight-bold text--primary">{{ $code->code }}</span>
                                        </td>
                                        <td data-label="@lang('Clicks')">{{ $code->total_clicks }}</td>
                                        <td data-label="@lang('Installs')">{{ $code->total_installs }}</td>
                                        <td data-label="@lang('Signups')">
                                            <span class="font-weight-bold">{{ $code->total_signups }}</span>
                                        </td>
                                        <td data-label="@lang('Bookings')">{{ $code->total_bookings }}</td>
                                        <td data-label="@lang('Earnings')">
                                            {{ showAmount($code->total_earnings) }} {{ __($general->cur_text) }}
                                        </td>
                                        <td data-label="@lang('Action')">
                                            <a href="{{ route('admin.referral.codes.details', $code->id) }}"
                                                class="icon-btn btn--primary">
                                                <i class="las la-desktop"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">@lang('No referral codes found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mt-30">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Recent Events')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Referrer')</th>
                                    <th>@lang('Referee')</th>
                                    <th>@lang('Code')</th>
                                    <th>@lang('Date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEvents as $event)
                                    <tr>
                                        <td data-label="@lang('Event')">
                                            @if ($event->type == 'install')
                                                <span class="badge badge--info">@lang('Install')</span>
                                            @elseif($event->type == 'signup')
                                                <span class="badge badge--success">@lang('Signup')</span>
                                            @elseif($event->type == 'booking')
                                                <span class="badge badge--warning">@lang('Booking')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Type')">{{ ucfirst($event->type) }}</td>
                                        <td data-label="@lang('Referrer')">
                                            @if ($event->referrer)
                                                {{ $event->referrer->fullname }}
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Referee')">
                                            @if ($event->referee)
                                                {{ $event->referee->fullname }}
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Code')">
                                            <span class="font-weight-bold">{{ $event->referralCode->code }}</span>
                                        </td>
                                        <td data-label="@lang('Date')">
                                            {{ showDateTime($event->triggered_at, 'd M Y h:i A') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">@lang('No recent events')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
