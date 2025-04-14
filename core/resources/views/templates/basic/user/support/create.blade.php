@extends($activeTemplate.'layouts.master')
@section('content')
<div class="container">
    <div class="row justify-content-center padding-top padding-bottom">
        <div class="col-md-12">
            <div class="card cmn--card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:7px">
                    <h6 class="title my-0">{{ __($pageTitle) }}</h6>
                    <a href="{{route('support_ticket') }}" class="btn btn-sm bg-white float-right support-ticket">
                        @lang('My Support Ticket')
                    </a>
                </div>

                <div class="card-body">
                    <form action="{{route('ticket.store')}}" method="post" enctype="multipart/form-data" onsubmit="return submitUserForm();">
                        @csrf
                        <div class="row gy-3">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="name">@lang('Name')</label>
                                <input type="text" name="name" value="{{@$user->firstname . ' '.@$user->lastname}}" class="form--control" placeholder="@lang('Enter your name')" readonly>
                            </div>
                            <div class="form-group col-md-6">
                                <label  class="form-label" for="email">@lang('Email address')</label>
                                <input type="email" name="email" value="{{@$user->email}}" class="form--control" placeholder="@lang('Enter your email')" readonly>
                            </div>

                            <div class="form-group col-md-6">
                                <label class="form-label" for="website">@lang('Subject')</label>
                                <input type="text" name="subject" value="{{old('subject')}}" class="form--control" placeholder="@lang('Subject')">
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="priority">@lang('Priority')</label>
                                <select name="priority" class="form--control">
                                    <option value="3">@lang('High')</option>
                                    <option value="2">@lang('Medium')</option>
                                    <option value="1">@lang('Low')</option>
                                </select>
                            </div>
                            <div class="col-12 form-group">
                                <label class="form-label" for="inputMessage">@lang('Message')</label>
                                <textarea name="message" id="inputMessage" rows="6" class="form--control">{{old('message')}}</textarea>
                            </div>
                            <div class="form-group col-sm-12">
                                <label class="form-label" for="inputAttachments" class="form--label">@lang('Attachments')</label>
                                <div class="form-group d-flex mb-2">
                                    <input type="file" class=" form-control" name="attachments[]" id="inputAttachments">
                                    <button class="ms-3 btn btn--base border-0 w-unset h-40 addFile" type="button"><i class="fas fa-plus"></i></button>
                                </div>
                                <div id="fileUploadsContainer"></div>
                                <span class="info fs--14px">@lang('Allowed File Extensions'): .@lang('jpg'), .@lang('jpeg'), .@lang('png'), .@lang('pdf'), .@lang('doc'), .@lang('docx')</span>
                            </div>
                            <div class="col-md-12">
                                <button class="btn btn--base h-40" type="submit" id="recaptcha"><i class="fa fa-paper-plane"></i>&nbsp;@lang('Submit')</button>
                            </div>
                        </div>
                    </form>
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
        $('.addFile').on('click', function() {
            $("#fileUploadsContainer").append(`
                    <div class="input-group my-3">
                        <input type="file" name="attachments[]" class="form-control" required />
                        <div class="support-input-group">
                            <span class=" h-100 btn btn--danger support-btn remove-btn"><i class="las la-times"></i></span>
                        </div>
                    </div>
                `)
        });
        $(document).on('click', '.remove-btn', function() {
            $(this).closest('.input-group').remove();
        });
    })(jQuery);
</script>
@endpush
