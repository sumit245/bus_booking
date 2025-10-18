@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper default-version">
        <div class="form-area bg_img" data-background="{{ asset('assets/admin/images/1.jpg') }}">
            <div class="form-wrapper">
                <h4 class="logo-text mb-15">@lang('Welcome to') <strong>{{ __($general->sitename) }}</strong></h4>
                <p>{{ __($pageTitle) }} @lang('to') {{ __($general->sitename) }} @lang('operator dashboard')</p>
                <form action="{{ route('operator.login') }}" method="POST" class="cmn-form mt-30">
                    @csrf
                    <div class="form-group">
                        <label for="email">@lang('Email Address')</label>
                        <input type="email" name="email"
                            class="form-control b-radius--capsule @error('email') is-invalid @enderror" id="email"
                            value="{{ old('email') }}" placeholder="@lang('Enter your email address')" required>
                        <i class="las la-envelope input-icon"></i>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password">@lang('Password')</label>
                        <input type="password" name="password"
                            class="form-control b-radius--capsule @error('password') is-invalid @enderror" id="password"
                            placeholder="@lang('Enter your password')" required>
                        <i class="las la-lock input-icon"></i>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group d-flex justify-content-between align-items-center">
                        <a href="{{ route('operator.password.reset') }}" class="text-muted text--small">
                            <i class="las la-lock"></i>@lang('Forgot password?')
                        </a>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="submit-btn mt-25 b-radius--capsule">
                            @lang('Login') <i class="las la-sign-in-alt"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-muted">@lang('Need help?') <a href="{{ route('contact') }}"
                            class="text--base">@lang('Contact Support')</a></p>
                </div>
            </div>
        </div><!-- login-area end -->
    </div>
@endsection
