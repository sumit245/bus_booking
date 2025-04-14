@extends($activeTemplate.$layout)

@section('content')
<div class="container padding-top padding-bottom">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card cmn--card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:10px">
                    <h5 class="card-title m-0 text-white">
                        @if($my_ticket->status == 0)
                        <span class="badge badge--success py-2 px-3">@lang('Open')</span>
                        @elseif($my_ticket->status == 1)
                        <span class="badge badge--primary py-2 px-3">@lang('Answered')</span>
                        @elseif($my_ticket->status == 2)
                        <span class="badge badge--warning py-2 px-3">@lang('Replied')</span>
                        @elseif($my_ticket->status == 3)
                        <span class="badge badge--dark py-2 px-3">@lang('Closed')</span>
                        @endif
                        [@lang('Ticket')#{{ $my_ticket->ticket }}] {{ $my_ticket->subject }}
                    </h5>
                    <button class="btn btn--dark w-auto h-auto" type="button" title="@lang('Close Ticket')" data-bs-toggle="modal" data-bs-target="#DelModal"><i class="fa fa-lg fa-times-circle"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accordionExample">
                        <div id="collapseThree" class="collapse show" aria-labelledby="headingThree" data-parent="#accordionExample">
                            @if($my_ticket->status != 4)
                            <form method="post" action="{{ route('ticket.reply', $my_ticket->id) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="replayTicket" value="1">
                                <div class="row justify-content-between pb-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <textarea name="message" class="form-control form--control shadow-none" id="inputMessage" placeholder="@lang('Your Reply')" rows="4" cols="10"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row justify-content-between w-100">
                                    <div class="col-md-8">
                                        <div class="row justify-content-between">
                                            <div class="col-md-11">
                                                <div class="form-group">
                                                    <label for="inputAttachments" class="form-label">@lang('Attachments')</label>

                                                    <div class="input-group">
                                                        <input type="file" name="attachments[]" id="inputAttachments" class="form--control form-control ps-2 py-1">
                                                        <a href="javascript:void(0)" class="btn btn--base btn-round ms-sm-4 ms-1 addFile px-3 radius-5">
                                                            <i class="fa fa-plus"></i>
                                                        </a>
                                                    </div>
                                                    <div id="fileUploadsContainer"></div>
                                                    <p class="mt-1 ticket-attachments-message text-muted fs-small">
                                                        @lang('Allowed File Extensions'): .@lang('jpg'), .@lang('jpeg'), .@lang('png'), .@lang('pdf'), .@lang('doc'), .@lang('docx')
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 pt-2">
                                        <button type="submit" class="btn btn--base mt-md-4 mt-2 h-40">
                                            <i class="fa fa-reply"></i> @lang('Reply')
                                        </button>
                                    </div>
                                </div>
                            </form>
                            @endif
                        </div>
                    </div>
                    <div class="row pt-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    @foreach($messages as $message)
                                    @if($message->admin_id == 0)
                                    <div class="row border border-primary border-radius-3 my-sm-3 my-2 py-3 mx-0 mx-sm-2" style="background-color: #dbe9ff">
                                        <div class="col-md-3 border--right text-right">
                                            <h5 class="my-3">{{ $message->ticket->name }}</h5>
                                        </div>
                                        <div class="col-md-9 ps-2">
                                            <p class="text-muted fw-bold">
                                                @lang('Posted on') {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                            <p>{{$message->message}}</p>
                                            @if($message->attachments()->count() > 0)
                                            <div class="mt-2">
                                                @foreach($message->attachments as $k=> $image)
                                                <a href="{{route('ticket.download',encrypt($image->id))}}" class="mr-3"><i class="fa fa-file"></i> @lang('Attachment') {{++$k}} </a>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @else
                                    <div class="row border border-warning border-radius-3 my-sm-3 my-2 py-3 mx-0 mx-sm-2" style="background-color: #ffd96729">
                                        <div class="col-md-3 border--right text-right">
                                            <h5 class="my-1">{{ $message->admin->name }}</h5>
                                            <p class="lead text-muted">@lang('Staff')</p>
                                        </div>
                                        <div class="col-md-9">
                                            <p class="text-muted fw-bold">
                                                @lang('Posted on') {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                            <p>{{$message->message}}</p>
                                            @if($message->attachments()->count() > 0)
                                            <div class="mt-2">
                                                @foreach($message->attachments as $k=> $image)
                                                <a href="{{route('ticket.download',encrypt($image->id))}}" class="mr-3"><i class="fa fa-file"></i> @lang('Attachment') {{++$k}} </a>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="DelModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('ticket.reply', $my_ticket->id) }}">
                @csrf
                <input type="hidden" name="replayTicket" value="2">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Confirmation')!</h5>
                    <button type="button" class="w-auto btn--close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
                </div>
                <div class="modal-body">
                    <strong class="text-dark">@lang('Are you sure you want to close this support ticket')?</strong>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn  btn--danger w-auto btn--sm px-3" data-bs-dismiss="modal"> @lang('Close')</button>
                    <button type="submit" class="btn btn--success btn--sm w-auto"> @lang("Confirm") </button>
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
        $('.delete-message').on('click', function(e) {
            $('.message_id').val($(this).data('id'));
        });
        $('.addFile').on('click', function() {
            $("#fileUploadsContainer").append(
                `<div class="input-group my-3">
                            <input type="file" name="attachments[]" id="inputAttachments" class="form-control form--control ps-2 py-1" required>
                            <a href="javascript:void(0)" class="btn btn--danger btn-round remove-btn px-3">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                    `
            )
        });
        $(document).on('click', '.remove-btn', function() {
            $(this).closest('.input-group').remove();
        });
    })(jQuery);
</script>
@endpush
