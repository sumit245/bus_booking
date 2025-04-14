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
                                    <th>@lang('Title')</th>
                                    <th>@lang('Icon')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($facilities as $item)
                                <tr>
                                    <td data-label="@lang('S.N.')">
                                        {{$item ->current_page-1 * $item ->per_page + $loop->iteration }}
                                    </td>
                                    <td data-label="@lang('Title')">
                                        {{ __($item->title) }}
                                    </td>

                                    <td data-label="@lang('Icon')">
                                        @php
                                            echo @$item->icon
                                        @endphp
                                    </td>
                                    <td data-label="@lang('Action')">
                                        <button type="button" class="icon-btn ml-1 editBtn"
                                                data-toggle="modal" data-target="#editModal"
                                                data-title="{{ __($item->title) }}"
                                                data-details="{{ __($item->details) }}"
                                                data-icon="{{ __($item->icon) }}"
                                                data-action="{{ route('admin.fleet.facilities.update', $item->id) }}"
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
                    {{ paginateLinks($facilities) }}
                </div>
            </div>
        </div>
    </div>


    {{-- Add METHOD MODAL --}}
    <div id="addModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add Facilities')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.facilities.store')}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-control-label font-weight-bold"> @lang('Title')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Title')" name="title" required>
                        </div>

                        <div class="form-group">
                            <label  class="form-control-label font-weight-bold"> @lang('Details')</label>
                            <textarea name="details" class="form-control" placeholder="@lang('Enter Facilities detail')" required></textarea>
                        </div>

                        <div class="form-group">
                            <label  class="form-control-label font-weight-bold">@lang('Icon')</label>
                            <div class="input-group has_append">
                                <input type="text" class="form-control icon-name" name="icon" value="" placeholder="Icon">

                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary iconPicker" data-icon="fas fa-home"
                                        role="iconpicker"></button>
                                </div>
                            </div>
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
                    <h5 class="modal-title"> @lang('Update Facilities')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label  class="form-control-label font-weight-bold"> @lang('Title')</label>
                            <input type="text" class="form-control" placeholder="@lang('Enter Title')" name="title" required>
                        </div>

                        <div class="form-group">
                            <label  class="form-control-label font-weight-bold"> @lang('Details')</label>
                            <textarea name="details" class="form-control" placeholder="@lang('Enter Facilities detail')" required></textarea>
                        </div>

                        <div class="form-group">
                            <label  class="form-control-label font-weight-bold">@lang('Icon')</label>
                            <div class="input-group has_append">
                                <input type="text" class="form-control icon-name" name="icon" value="" placeholder="Icon">

                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary iconPicker" data-icon="las la-home"
                                        role="iconpicker"></button>
                                </div>
                            </div>
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
                    <h5 class="modal-title"> @lang('Delete Facilities')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.facilities.delete')}}" method="POST">
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

@push('script-lib')
    <script src="{{ asset('assets/admin/js/bootstrap-iconpicker.bundle.min.js') }}"></script>
@endpush
@push('script')
    <script>
        (function ($) {
            "use strict";

            $('.removeBtn').on('click', function () {
                var modal = $('#removeModal');
                modal.find('input[name=id]').val($(this).data('id'));
                modal.modal('show');
            });

            $('#editModal').on('shown.bs.modal', function (e) {
                $(document).off('focusin.modal');
            });
            $('#addModal').on('shown.bs.modal', function (e) {
                $(document).off('focusin.modal');
            });

            $('.addBtn').on('click', function () {
                var modal = $('#addModal');
                modal.modal('show');
            });

            $('.editBtn').on('click', function () {
                var modal = $('#editModal');
                modal.find('form').attr('action' ,$(this).data('action'));
                modal.find('input[name=title]').val($(this).data('title'));
                modal.find('textarea[name=details]').val($(this).data('details'));
                modal.find('input[name=icon]').val($(this).data('icon'));
                modal.modal('show');
            });

            $('.iconPicker').iconpicker({
                align: 'center', // Only in div tag
                arrowClass: 'btn-danger',
                arrowPrevIconClass: 'fas fa-angle-left',
                arrowNextIconClass: 'fas fa-angle-right',
                cols: 10,
                footer: true,
                header: true,
                icon: 'fas fa-bomb',
                iconset: 'fontawesome5',
                labelHeader: '{0} of {1} pages',
                labelFooter: '{0} - {1} of {2} icons',
                placement: 'bottom', // Only in button tag
                rows: 5,
                search: false,
                searchText: 'Search icon',
                selectedClass: 'btn--success',
                unselectedClass: ''
            }).on('change', function (e) {
                $(this).parent().siblings('.icon-name').val(`<i class="${e.icon}"></i>`);
            });
        })(jQuery);

    </script>

@endpush
