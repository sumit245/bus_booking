@extends($activeTemplate.'layouts.authenticate')
@section('content')
    @php
        $content = getContent('sign_in.content', true);
    @endphp
    <!-- Account Section Starts Here -->
    <section class="account-section bg_img" style="background: url({{getImage('assets/images/frontend/sign_in/'. @$content->data_values->background_image, "1920x1280") }}) bottom left;">
        <span class="spark"></span>
        <span class="spark2"></span>
        <div class="account-wrapper">
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
                    <form method="POST" class="account-form row" action="{{ route('user.login')}}" onsubmit="return submitUserForm();">
                    @csrf
                    <div class="col-lg-12">
                        <div class="form--group">
                            <label for="username">@lang('Username')</label>
                            <input id="username" name="username" type="text" class="form--control" placeholder="@lang('Enter Your username')" required>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form--group">
                            <label for="password">@lang('Password')</label>
                            <input id="password" type="password" name="password" class="form--control" placeholder="@lang('Enter Your Password')" required>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form--group">
                            @php echo loadReCaptcha() @endphp
                        </div>
                    </div>
                    @include($activeTemplate.'partials.custom_captcha')
                    <div class="col-lg-12 d-flex justify-content-between">
                        <div class="form--group custom--checkbox">
                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember">@lang('Remember Me')</label>
                        </div>
                        <div class="">
                            <a href="{{route('user.password.request')}}">@lang('Forgot Password?')</a>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form--group">
                            <button class="account-button w-100" type="submit">@lang('Log In')</button>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="account-page-link">
                            <p>@lang('Don\'t have any Account?') <a href="{{ route('user.register') }}">@lang('Sign Up')</a></p>
                        </div>
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
