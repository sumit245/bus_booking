@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card b-radius--10 ">
                <div class="card-body">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Seat Layout')</th>
                                    <th>@lang('No of Deck')</th>
                                    <th>@lang('Total Seat')</th>
                                    <th>@lang('Facilities')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($fleetType as $item)
                                <tr>
                                    <td data-label="@lang('Name')">
                                        {{ __($item->name) }}
                                    </td>
                                    <td data-label="@lang('Seat Layout')">
                                        {{ __($item->seat_layout) }}
                                    </td>
                                    <td data-label="@lang('No of Deck')">
                                        {{ __($item->deck) }}
                                    </td>
                                    <td data-label="@lang('Total Seat')">
                                        {{ array_sum($item->deck_seats) }}
                                    </td>
                                    <td data-label="@lang('Facilities')">
                                        @if ($item->facilities)
                                            {{ __(implode(',', $item->facilities)) }}
                                        @else
                                            @lang('No facilities')
                                        @endif
                                    </td>
                                    <td data-label="@lang('Status')">
                                        @if($item->status == 1)
                                        <span class="text--small badge font-weight-normal badge--success">@lang('Active')</span>
                                        @else
                                        <span class="text--small badge font-weight-normal badge--warning">@lang('Disabled')</span>
                                        @endif
                                    </td>
                                    <td data-label="@lang('Action')">
                                        <button type="button" class="icon-btn ml-1 editBtn"
                                                data-toggle="modal" data-target="#editModal"
                                                data-fleet_type = "{{ $item }}"
                                                data-original-title="@lang('Update')">
                                            <i class="la la-pen"></i>
                                        </button>

                                        @if ($item->status != 1)
                                            <button type="button"
                                            class="icon-btn btn--success ml-1 activeBtn"
                                            data-toggle="modal" data-target="#activeModal"
                                            data-id="{{ $item->id }}"
                                            data-type_name="{{ $item->name }}"
                                            data-original-title="@lang('Active')">
                                            <i class="la la-eye"></i>
                                        </button>
                                        @else
                                            <button type="button"
                                                class="icon-btn btn--danger ml-1 disableBtn"
                                                data-toggle="modal" data-target="#disableModal"
                                                data-id="{{ $item->id }}"
                                                data-type_name="{{ $item->name }}"
                                                data-original-title="@lang('Disable')">
                                                <i class="la la-eye-slash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer py-4">
                    {{ paginateLinks($fleetType) }}
                </div>
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add Fleet Type')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.type.store')}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Name')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Fleet Name')" name="name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Seat Layout')</label>
                            <select name="seat_layout" class="form-control">
                                <option value="">@lang('Select an option')</option>
                                @foreach ($seatLayouts as $item)
                                    <option value="{{ $item->layout }}">{{ __($item->layout) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('No of Deck')</label>
                            <input type="number" min="0" class="form-control" placeholder="@lang('Enter Number of Deck')" name="deck" required>
                        </div>
                        <div class="showSeat"></div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold" for="facilities">@lang('Facilities')</label>
                            <select class="select2-auto-tokenize" name="facilities[]" id="facilities" multiple="multiple">
                                @foreach ($facilities as $item)
                                <option value="{{ $item->data_values->title }}" selected>{{ $item->data_values->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold">@lang('AC status') </label>
                            <input type="checkbox" data-width="100%" data-onstyle="-success" data-offstyle="-danger"
                                   data-toggle="toggle" data-on="@lang('Ac')" data-off="@lang('Non Ac')" name="has_ac">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--primary">@lang('Save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Update METHOD MODAL --}}
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Update Fleet Type')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Name')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Fleet Name')" name="name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Seat Layout')</label>
                            <select name="seat_layout" class="form-control">
                                <option value="">@lang('Select an option')</option>
                                @foreach ($seatLayouts as $item)
                                    <option value="{{ $item->layout }}">{{ __($item->layout) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('No of Deck')</label>
                            <input type="number" min="0" class="form-control" placeholder="@lang('Enter Number of Deck')" name="deck" required>
                        </div>
                        <div class="showSeat"></div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold" for="facilities1">@lang('Facilities')</label>
                            <select class="select2-auto-tokenize" name="facilities[]" id="facilities1" multiple="multiple">
                                @foreach ($facilities as $item)
                                <option value="{{ $item->data_values->title }}" selected>{{ $item->data_values->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold">@lang('AC status') </label>
                            <input type="checkbox" data-width="100%" data-onstyle="-success" data-offstyle="-danger"
                                    data-toggle="toggle" data-on="@lang('Ac')" data-off="@lang('Non Ac')" name="has_ac">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--primary">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- active METHOD MODAL --}}
    <div id="activeModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Active Fleet Type')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.type.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to active') <span class="font-weight-bold type_name"></span> @lang('fleet type')?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--primary">@lang('Active')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- disable METHOD MODAL --}}
    <div id="disableModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Disable Fleet Type')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.type.active.disable')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <p>@lang('Are you sure to disable') <span class="font-weight-bold type_name"></span> @lang('fleet type')?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--danger">@lang('Disable')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="javascript:void(0)" class="btn btn-sm btn--primary box--shadow1 text--small addBtn"><i class="fa fa-fw fa-plus"></i>@lang('Add New')</a>
@endpush

@push('script')

    <script>
        (function ($) {
            "use strict";

            $('.disableBtn').on('click', function () {
                var modal = $('#disableModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.type_name').text($(this).data('type_name'));
                modal.modal('show');
            });

            $('.activeBtn').on('click', function () {
                var modal = $('#activeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.find('.type_name').text($(this).data('type_name'));
                modal.modal('show');
            });

            $('.addBtn').on('click', function () {
                $('.showSeat').empty();
                var modal = $('#addModal');
                modal.modal('show');
            });

            $('.select2-auto-tokenize').select2({
                tags: true,
                tokenSeparators: [',']
            });


            $(document).on('click', '.editBtn', function () {
                var modal   = $('#editModal');
                var data    = $(this).data('fleet_type');
                var link    = `{{ route('admin.fleet.type.update', '') }}/${data.id}`;
                var deckNumber = data.deck;
                modal.find('input[name=name]').val(data.name);
                modal.find('input[name=deck]').val(deckNumber);
                modal.find('select[name=seat_layout]').val(data.seat_layout);
                modal.find('select[name="facilities[]"]').val(data.facilities).select2();
                if(data.has_ac == 1){
                    modal.find('input[name=has_ac]').bootstrapToggle('on');
                }else{
                    modal.find('input[name=has_ac]').bootstrapToggle('off');
                }
                modal.find('form').attr('action', link);

                var fields =``;

                $.each(data.deck_seats, function (i, val) {
                    fields +=`<div class="form-group">
                            <label class="form-control-label font-weight-bold"> Seats of Deck - ${i + 1} </label>
                            <input type="text" class="form-control" value="${val}" placeholder="@lang('Enter Number of Seat')" name="deck_seats[]" required>
                        </div>`;
                });
                $('.showSeat').html(fields);
                modal.modal('show');
            });

            $('input[name=deck]').on('input', function(){
                $('.showSeat').empty();
                for(var deck=1; deck<= $(this).val(); deck++){
                    $('.showSeat').append(`
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> Seats of Deck - ${deck} </label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Number of Seat')" name="deck_seats[]" required>
                        </div>
                    `);
                }
            })

        })(jQuery);

    </script>

@endpush
