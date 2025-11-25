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
                                    <th>@lang('Beneficiary')</th>
                                    <th>@lang('Referrer')</th>
                                    <th>@lang('Event Type')</th>
                                    <th>@lang('Reward Type')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rewards as $reward)
                                    <tr>
                                        <td data-label="@lang('Beneficiary')">
                                            @if ($reward->beneficiary)
                                                <a href="{{ route('admin.users.detail', $reward->beneficiary_user_id) }}">
                                                    {{ $reward->beneficiary->fullname }}
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $reward->beneficiary->mobile }}</small>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Referrer')">
                                            @if ($reward->event && $reward->event->referrer)
                                                {{ $reward->event->referrer->fullname }}
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Event')">
                                            @if ($reward->event)
                                                @if ($reward->event->type == 'install')
                                                    <span class="badge badge--info">@lang('Install')</span>
                                                @elseif($reward->event->type == 'signup')
                                                    <span class="badge badge--success">@lang('Signup')</span>
                                                @elseif($reward->event->type == 'booking')
                                                    <span class="badge badge--warning">@lang('Booking')</span>
                                                @endif
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td data-label="@lang('Type')">
                                            @if ($reward->reward_type == 'fixed')
                                                @lang('Fixed')
                                            @elseif($reward->reward_type == 'percent')
                                                @lang('Percent')
                                            @elseif($reward->reward_type == 'percent_of_ticket')
                                                @lang('% of Ticket')
                                            @endif
                                            @if ($reward->basis_amount > 0)
                                                <br><small class="text-muted">@lang('Basis'):
                                                    {{ showAmount($reward->basis_amount) }}</small>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Amount')">
                                            <span class="font-weight-bold">
                                                {{ showAmount($reward->amount_awarded) }} {{ __($general->cur_text) }}
                                            </span>
                                        </td>
                                        <td data-label="@lang('Status')">
                                            @if ($reward->status == 'pending')
                                                <span class="badge badge--warning">@lang('Pending')</span>
                                            @elseif($reward->status == 'confirmed')
                                                <span class="badge badge--success">@lang('Confirmed')</span>
                                            @elseif($reward->status == 'reversed')
                                                <span class="badge badge--danger">@lang('Reversed')</span>
                                                @if ($reward->reason)
                                                    <br><small class="text-muted">{{ $reward->reason }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td data-label="@lang('Date')">
                                            {{ showDateTime($reward->created_at, 'd M Y h:i A') }}
                                            @if ($reward->credited_at)
                                                <br><small class="text-success">@lang('Credited'):
                                                    {{ showDateTime($reward->credited_at, 'd M Y') }}</small>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Action')">
                                            @if ($reward->status == 'pending')
                                                <form action="{{ route('admin.referral.rewards.confirm', $reward->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="icon-btn btn--success"
                                                        data-toggle="tooltip" title="@lang('Confirm')">
                                                        <i class="las la-check"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($reward->status != 'reversed')
                                                <button type="button" class="icon-btn btn--danger reverseBtn"
                                                    data-toggle="tooltip" title="@lang('Reverse')"
                                                    data-id="{{ $reward->id }}"
                                                    data-amount="{{ showAmount($reward->amount_awarded) }}">
                                                    <i class="las la-undo"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">@lang('No rewards found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($rewards->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($rewards) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Reverse Reward Modal -->
    <div class="modal fade" id="reverseModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Reverse Reward')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST" id="reverseForm">
                    @csrf
                    <div class="modal-body">
                        <p>@lang('Are you sure you want to reverse this reward of') <strong id="rewardAmount"></strong>?</p>
                        <div class="form-group">
                            <label>@lang('Reason')</label>
                            <input type="text" name="reason" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--danger">@lang('Reverse')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";

            $('.reverseBtn').on('click', function() {
                var modal = $('#reverseModal');
                var id = $(this).data('id');
                var amount = $(this).data('amount');

                modal.find('#rewardAmount').text(amount + ' {{ __($general->cur_text) }}');
                modal.find('#reverseForm').attr('action',
                    '{{ route('admin.referral.rewards.reverse', '') }}/' + id);
                modal.modal('show');
            });

        })(jQuery);
    </script>
@endpush
