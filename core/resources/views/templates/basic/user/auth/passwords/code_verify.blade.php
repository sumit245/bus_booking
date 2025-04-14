@extends($activeTemplate.'layouts.authenticate')
@section('content')
@php
$content = getContent('verify_code.content', true);
@endphp
<!-- Account Section Starts Here -->
<section class="account-section bg_img" style="background: url({{ getImage('assets/images/frontend/verify_code/'. @$content->data_values->background_image, "1920x1280") }}) bottom left;">
    <div class="account-wrapper">
        <div class="account-form-wrapper">
            <div class="account-header">
                <div class="left-content">
                    <div class="logo mb-4">
                        <a href="{{ route('home') }}"><img src="{{ getImage(imagePath()['logoIcon']['path'].'/logo.png') }}" alt="Logo"></a>
                    </div>
                    <h3 class="title">{{ __(@$pageTitle) }}</h3>
                    <span></span>
                </div>
            </div>
            <form class="account-form row" action="{{ route('user.password.verify.code') }}" method="POST" class="cmn-form mt-30">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="col-lg-12">
                    <div class="form--group">
                        <label for="code">@lang('Verification Code')</label>
                        <input id="code" name="code" type="text" class="form--control" placeholder="@lang('Enter Your username')" required>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-between">
                    <div class="form--group">
                        <button class="account-button" type="submit">@lang('Verify Code')</button>
                    </div>
                    <div class="">
                        <a class="link" href="{{ route('user.password.request') }}">@lang('Try to send again')</a>
                    </div>
                </div>

                <div class="col-lg-12">
                    <p>@lang('Please check including your Junk/Spam Folder. if not found, you can')</p>
                </div>

            </form>
        </div>
    </div>
</section>
<!-- Account Section Ends Here -->
@endsection
@push('script')
<script>
    "use strict";

    function submitUserForm() {
        var response = grecaptcha.getResponse();
        if (response.length == 0) {
            document.getElementById('g-recaptcha-error').innerHTML = '<span class="text-danger">@lang("Captcha field is required.")</span>';
            return false;
        }
        return true;
    }
</script>
@endpush
