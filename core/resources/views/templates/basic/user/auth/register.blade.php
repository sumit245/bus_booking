@extends($activeTemplate.'layouts.authenticate')
@section('content')
@php
$content = getContent('sign_up.content', true);
@endphp
<!-- Account Section Starts Here -->
<section class="account-section bg_img" style="background: url({{getImage('assets/images/frontend/sign_up/'. @$content->data_values->background_image, "1920x1280") }}) bottom left;">
    <span class="spark"></span>
    <span class="spark2"></span>
    <div class="account-wrapper  sign-up">
        <div class="account-form-wrapper">
            <div class="account-header">
                <div class="left-content">
                    <div class="logo mb-4">
                        <a href="{{ route('home') }}"><img src="{{ getImage(imagePath()['logoIcon']['path'].'/logo.png') }}" alt="Logo"></a>
                    </div>
                    <h3 class="title">{{ __(@$content->data_values->heading) }}</h3>
                    <span>{{ __(@$content->data_values->sub_heading) }}</span>
                </div>
            </div>
            <form class="account-form row" action="{{ route('user.register') }}" method="POST" onsubmit="return submitUserForm();">
                @csrf

                <div class="col-sm-6 col-xl-6">
                    <div class="form--group">
                        <label for="firstname">@lang('First Name') <span>*</span></label>
                        <input id="firstname" type="text" class="form--control" name="firstname" value="{{ old('firstname') }}" placeholder="@lang('Enter Your First Name')" required>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <div class="form--group">
                        <label for="lastname">@lang('Last Name') <span>*</span></label>
                        <input id="lastname" type="text" class="form--control" name="lastname" value="{{ old('lastname') }}" placeholder="@lang('Enter Your Last Name')" required>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <div class="form--group">
                        <label for="country">@lang('Country')</label>
                        <select name="country" id="country" class="form--control">
                            @foreach($countries as $key => $country)
                                <option data-mobile_code="{{ $country->dial_code }}" value="{{ $country->country }}" data-code="{{ $key }}">{{ __($country->country) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <label for="mobile">@lang('Mobile') <span>*</span></label>
                    <div class="form--group">
                        <div class="input-group flex-nowrap">
                                <span class="input-group-text mobile-code border-0 h-40"></span>
                                <input type="hidden" name="mobile_code">
                                <input type="hidden" name="country_code">
                            <input type="number" name="mobile" id="mobile" value="{{ old('mobile') }}" class="form--control ps-2  checkUser" placeholder="@lang('Your Phone Number')">
                        </div>
                        <small class="text-danger mobileExist"></small>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <div class="form--group">
                        <label for="username">@lang('Username') <span>*</span></label>
                        <input id="username" type="text" class="form--control checkUser" name="username" value="{{ old('username') }}" placeholder="@lang('Enter Username')" required>
                        <small class="text-danger usernameExist"></small>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <div class="form--group">
                        <label for="email">@lang('Email') <span>*</span></label>
                        <input id="email" type="email" class="form--control checkUser" name="email" value="{{ old('email') }}" placeholder="@lang('Enter Your Email')" required>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6 hover-input-popup">
                    <div class="form--group">
                        <label for="password">@lang('Password') <span>*</span></label>
                        <input id="password" type="password" class="form--control" name="password" placeholder="@Lang('Enter Your Password')" required>
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

                <div class="col-sm-6 col-xl-6">
                    <div class="form--group">
                        <label for="password-confirm">@lang('Confirm Password') <span>*</span></label>
                        <input id="password-confirm" type="password" class="form--control" name="password_confirmation" placeholder="@lang('Confirm Password')" required>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6 mb-3">
                    @php echo loadReCaptcha() @endphp
                </div>
                @include($activeTemplate.'partials.custom_captcha')

                @if($general->agree)
                <div class="col-sm-6 col-xl-6">
                    <div class="form--group custom--checkbox">
                        <input type="checkbox"  name="agree" id="agree">
                        <label for="agree">@lang('Accepting all') &nbsp;</label>
                        @php
                            $policies = getContent('policies.element',null,3);
                        @endphp
                        @foreach ($policies as $policy)
                            <a href="{{ route('policy.details', [$policy->id, slug($policy->data_values->title)]) }}">@php
                                echo $policy->data_values->title
                            @endphp </a> @if(!$loop->last) , @endif
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="col-md-12">
                    <div class="form--group">
                        <button class="account-button w-100">@lang('Sign Up')</button>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="account-page-link">
                        <p>@lang('Already have an Account?') <a href="{{ route('user.login') }}">@lang('Sign In')</a></p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<!-- Account Section Ends Here -->

<div class="modal fade" id="existModalCenter" tabindex="-1" role="dialog" aria-labelledby="existModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"  id="existModalLongTitle">@lang('You are with us')</h5>
                <button type="button" class="w-auto btn--close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">
                <strong class="text-dark">@lang('You already have an account please Sign in ')</strong>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--danger btn--sm w-auto" data-bs-dismiss="modal">@lang('Close')</button>
                <a href="{{ route('user.login') }}" class="btn btn--success btn--sm w-auto">@lang('Login')</a>
            </div>
        </div>
    </div>
</div>
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
        "use strict";
        function submitUserForm() {
            var response = grecaptcha.getResponse();
            if (response.length == 0) {
                document.getElementById('g-recaptcha-error').innerHTML = '<span class="text-danger">@lang("Captcha field is required.")</span>';
                return false;
            }
            return true;
        }
        (function ($) {
            @if($mobile_code)
            $(`option[data-code={{ $mobile_code }}]`).attr('selected','');
            @endif

            $('select[name=country]').change(function(){
                $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                $('.mobile-code').text('+'+$('select[name=country] :selected').data('mobile_code'));
            });
            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
            $('.mobile-code').text('+'+$('select[name=country] :selected').data('mobile_code'));
            @if($general->secure_password)
                $('input[name=password]').on('input',function(){
                    secure_password($(this));
                });
            @endif

            $('.checkUser').on('focusout',function(e){
                var url = '{{ route('user.checkUser') }}';
                var value = $(this).val();
                var token = '{{ csrf_token() }}';
                if ($(this).attr('name') == 'mobile') {
                    var mobile = `${$('.mobile-code').text().substr(1)}${value}`;
                    var data = {mobile:mobile,_token:token}
                }
                if ($(this).attr('name') == 'email') {
                    var data = {email:value,_token:token}
                }
                if ($(this).attr('name') == 'username') {
                    var data = {username:value,_token:token}
                }
                $.post(url,data,function(response) {
                    if (response['data'] && response['type'] == 'email') {
                    $('#existModalCenter').modal('show');
                    }else if(response['data'] != null){
                    $(`.${response['type']}Exist`).text(`${response['type']} already exist`);
                    }else{
                    $(`.${response['type']}Exist`).text('');
                    }
                });
            });

        })(jQuery);

    </script>
@endpush
