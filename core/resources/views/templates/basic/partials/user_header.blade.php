@php
$content = getContent('contact.content', true);
@endphp
<!-- Header Section Starts Here -->
<div class="header-top">
    <div class="container">
        <div class="header-top-area">
            <ul class="left-content">
                <li>
                    <i class="las la-phone"></i>
                    <a href="tel:{{ __(@$content->data_values->contact_number) }}">
                        {{ __(@$content->data_values->contact_number) }}
                    </a>
                </li>
                <li>
                    <i class="las la-envelope-open"></i>
                    <a href="mailto:{{ __(@$content->data_values->email) }}">
                        {{ __(@$content->data_values->email) }}
                    </a>
                </li>
            </ul>
            <div class="right-content">
                <div>
                    <select class="langSel form--control">
                        @foreach($language as $item)
                        <option value="{{$item->code}}" @if(session('lang')==$item->code) selected @endif>{{ __($item->name) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="header-bottom">
    <div class="container">
        <div class="header-bottom-area">
            <div class="logo">
                <a href="{{ route('home') }}">
                    <img src="{{ getImage(imagePath()['logoIcon']['path'].'/logo.png') }}" alt="@lang('Logo')">
                </a>
            </div> <!-- Logo End -->
            <ul class="menu">
                <li>
                    <a href="{{ route('user.home') }}">@lang('Dashboard')</a>
                </li>
                <li>
                    <a href="javascript::void()">@lang('Booking')</a>
                    <ul class="sub-menu">
                        <li>
                            <a href="{{ route('ticket') }}">@lang('Buy Ticket')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.ticket.history') }}">@lang('Booking History')</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="javascript::void()">@lang('Support Ticket')</a>
                    <ul class="sub-menu">
                        <li>
                            <a href="{{route('ticket.open')}}">@lang('Create New')</a>
                        </li>
                        <li>
                            <a href="{{route('support_ticket')}}">@lang('Tickets')</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#0">@lang('Profile')</a>
                    <ul class="sub-menu">
                        <li>
                            <a href="{{ route('user.profile.setting') }}">@lang('Profile')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.change.password') }}">@lang('Change Password')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.logout') }}">@lang('Logout')</a>
                        </li>
                    </ul>
                </li>
            </ul>
            <div class="d-flex flex-wrap algin-items-center">
                <a href="{{ route('ticket') }}" class="cmn--btn btn--sm">@lang('Buy Tickets')</a>
                <div class="header-trigger-wrapper d-flex d-lg-none ms-4">
                    <div class="header-trigger d-block d-lg-none">
                        <span></span>
                    </div>
                    <div class="top-bar-trigger">
                        <i class="las la-ellipsis-v"></i>
                    </div>
                </div><!-- Trigger End-->
            </div>
        </div>
    </div>
</div>
<!-- Header Section Ends Here -->

@push('script')
<script>
    $(document).ready(function() {
        "use strict";
        $(".langSel").on("change", function() {
            window.location.href = "{{route('home')}}/change/" + $(this).val();
        });
    });
</script>
@endpush
