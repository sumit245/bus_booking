@extends($activeTemplate.'layouts.authenticate')
@section('content')
    @php
        $content = getContent('forgot_password.content', true);
    @endphp
    <!-- Account Section Starts Here -->
    <section class="account-section bg_img" style="background: url({{getImage('assets/images/frontend/forgot_password/'. @$content->data_values->background_image, "1920x1280") }}) bottom left;">
        <span class="spark"></span>
        <span class="spark2"></span>
        <div class="account-wrapper">
            <div class="account-form-wrapper">
                <div class="account-header">
                    <div class="left-content">
                        <div class="logo mb-4">
                            <a href="{{ route('home') }}"><img src="{{ getImage(imagePath()['logoIcon']['path'].'/logo.png') }}" alt="Logo"></a>
                        </div>
                         <h3 class="title">{{ __(@$pageTitle) }}</h3>
                    </div>
                </div>

                <form class="account-form row" method="POST" action="{{ route('user.password.update') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="col-lg-12">
                        <div class="form--group">
                            <label for="password" class="mb-0 lh-1">@lang('Password')</label>
                            <div class="hover-input-popup">
                                <input id="password" type="password" class="form--control h-40" @error('password') is-invalid @enderror" name="password" required>
                                @if($general->secure_password)
                                    <div class="input-popup">
                                      <p class="error lower">@lang('1 small letter minimum')</p>
                                      <p class="error capital">@lang('1 capital letter minimum')</p>
                                      <p class="error number">@lang('1 number minimum')</p>
                                      <p class="error special">@lang('1 special character minimum')</p>
                                      <p class="error minimum">@lang('6 character password')</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form--group">
                            <label for="password-confirm" class="mb-0 lh-1">@lang('Confirm Password')</label>
                            <input id="password-confirm" type="password" class="form--control h-40" name="password_confirmation" required>
                        </div>
                    </div>


                    <div class="col-md-12">
                        <div class=" d-flex justify-content-between align-items-center w-100">
                            <div class="form--group mb-0">
                                <button class="account-button" type="submit">@lang('Reset Password')</button>
                            </div>
                            <a href="{{ route('user.login') }}" >@lang('Login Here')</a>
                        </div>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </section>
    <!-- Account Section Ends Here -->
@endsection
@push('style')
    <style>
        .hover-input-popup {
            position: relative;
        }
        .hover-input-popup:hover .input-popup {
            opacity: 1;
            visibility: visible;
        }
        .input-popup {
            position: absolute;
            bottom: 130%;
            left: 50%;
            width: 280px;
            background-color: #1a1a1a;
            color: #fff;
            padding: 20px;
            border-radius: 5px;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            -ms-border-radius: 5px;
            -o-border-radius: 5px;
            -webkit-transform: translateX(-50%);
            -ms-transform: translateX(-50%);
            transform: translateX(-50%);
            opacity: 0;
            visibility: hidden;
            -webkit-transition: all 0.3s;
            -o-transition: all 0.3s;
            transition: all 0.3s;
        }
        .input-popup::after {
            position: absolute;
            content: '';
            bottom: -19px;
            left: 50%;
            margin-left: -5px;
            border-width: 10px 10px 10px 10px;
            border-style: solid;
            border-color: transparent transparent #1a1a1a transparent;
            -webkit-transform: rotate(180deg);
            -ms-transform: rotate(180deg);
            transform: rotate(180deg);
        }
        .input-popup p {
            padding-left: 20px;
            position: relative;
        }
        .input-popup p::before {
            position: absolute;
            content: '';
            font-family: 'Line Awesome Free';
            font-weight: 900;
            left: 0;
            top: 4px;
            line-height: 1;
            font-size: 18px;
        }
        .input-popup p.error {
            text-decoration: line-through;
        }
        .input-popup p.error::before {
            content: "\f057";
            color: #ea5455;
        }
        .input-popup p.success::before {
            content: "\f058";
            color: #28c76f;
        }
    </style>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";
            @if($general->secure_password)
                $('input[name=password]').on('input',function(){
                    secure_password($(this));
                });
            @endif
        })(jQuery);
    </script>
@endpush
