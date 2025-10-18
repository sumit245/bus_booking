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
                                    <th>@lang('S.N.')</th>
                                    <th>@lang('Layout')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($layouts as $item)
                                <tr>
                                    <td data-label="@lang('S.N.')">
                                        {{ $item ->current_page-1 * $item ->per_page + $loop->iteration }}
                                    </td>
                                    <td data-label="@lang('Layout')">
                                        {{ __($item->layout) }}
                                    </td>
                                    <td data-label="@lang('Action')">
                                        <button type="button" class="icon-btn ml-1 editBtn"
                                                data-toggle="modal" data-target="#editModal"
                                                data-layout="{{ __($item->layout) }}"
                                                data-action="{{ route('admin.fleet.seat.layouts.update', $item->id) }}"
                                                data-original-title="@lang('Update')">
                                            <i class="la la-pen"></i>
                                        </button>

                                        <button type="button"
                                                class="icon-btn btn--danger ml-1 removeBtn"
                                                data-toggle="modal" data-target="#removeModal"
                                                data-id="{{ $item->id }}"
                                                data-original-title="@lang('Delete')">
                                            <i class="las la-trash"></i>
                                        </button>
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
                    {{ paginateLinks($layouts) }}
                </div>
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add Seat Layouts')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.seat.layouts.store')}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Layout')</label>
                            <input type="text" class="form-control" placeholder="@lang('2 x 3')" name="layout" required>
                            <small class="text-primary">@lang('Just type left and right value, a seperator (x) will be added automatically')</small>
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
                    <h5 class="modal-title"> @lang('Update Seat Layouts')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Layout')</label>
                            <input type="text" class="form-control" placeholder="@lang('2 x 3')" name="layout" required>
                            <small class="text-primary">@lang('Just type left and right value, a seperator (x) will be added automatically')</small>
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

    {{-- remove METHOD MODAL --}}
    <div id="removeModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Delete Seat Layouts')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.seat.layouts.delete')}}" method="POST">
                    @csrf
                    <input type="text" name="id" hidden="true">
                    <div class="modal-body">
                        <strong>@lang('Are you sure, you want to delete this?')</strong>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--danger">@lang('Delete')</button>
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

            $(document).on('keypress', 'input[name=layout]', function(e){
                var layout = $(this).val();
                if(layout != ''){
                    if(layout.length > 0 && layout.length <= 1)
                    $(this).val(`${layout} x `);

                    if(layout.length > 4) {
                        return false;
                    }
                }
            });

            $(document).on('keyup', 'input[name=layout]', function(e){
                var key = event.keyCode || event.charCode;
                if( key == 8 || key == 46 ){
                    console.log($(this).val());
                    $(this).val($(this).val().replace(' x ',''));
                }

            });

            $('.removeBtn').on('click', function () {
                var modal = $('#removeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.modal('show');
            });

            $('.addBtn').on('click', function () {
                var modal = $('#addModal');
                modal.modal('show');
            });

            $('.editBtn').on('click', function () {
                var modal = $('#editModal');
                modal.find('form').attr('action' ,$(this).data('action'));
                modal.find('input[name=layout]').val($(this).data('layout'));
                modal.modal('show');
            });

        })(jQuery);

    </script>

@endpush
