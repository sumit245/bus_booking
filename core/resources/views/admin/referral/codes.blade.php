@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('Code')</th>
                                    <th>@lang('Source')</th>
                                    <th>@lang('Stats')</th>
                                    <th>@lang('Earnings')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Created')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($codes as $code)
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
                                        <td data-label="@lang('Source')">
                                            <span class="badge badge--dark">{{ strtoupper($code->source) }}</span>
                                        </td>
                                        <td data-label="@lang('Stats')">
                                            <small>
                                                @lang('Clicks'): {{ $code->total_clicks }}<br>
                                                @lang('Installs'): {{ $code->total_installs }}<br>
                                                @lang('Signups'): <strong>{{ $code->total_signups }}</strong><br>
                                                @lang('Bookings'): {{ $code->total_bookings }}
                                            </small>
                                        </td>
                                        <td data-label="@lang('Earnings')">
                                            {{ showAmount($code->total_earnings) }} {{ __($general->cur_text) }}
                                        </td>
                                        <td data-label="@lang('Status')">
                                            @if ($code->is_active)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Inactive')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Created')">
                                            {{ showDateTime($code->created_at, 'd M Y') }}
                                        </td>
                                        <td data-label="@lang('Action')">
                                            <a href="{{ route('admin.referral.codes.details', $code->id) }}"
                                                class="icon-btn btn--primary" data-toggle="tooltip"
                                                title="@lang('Details')">
                                                <i class="las la-desktop"></i>
                                            </a>
                                            <form action="{{ route('admin.referral.codes.toggle', $code->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit"
                                                    class="icon-btn btn--{{ $code->is_active ? 'danger' : 'success' }}"
                                                    data-toggle="tooltip" title="@lang($code->is_active ? 'Deactivate' : 'Activate')">
                                                    <i class="las la-{{ $code->is_active ? 'ban' : 'check' }}"></i>
                                                </button>
                                            </form>
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
                @if ($codes->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($codes) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
