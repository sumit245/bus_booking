@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper default-version">
        <div class="form-area bg_img" data-background="{{ asset('assets/admin/images/1.jpg') }}">
            <div class="form-wrapper">
                <h4 class="logo-text mb-15">@lang('Welcome to') <strong>{{ __($general->sitename) }}</strong></h4>
                <p>{{ __($pageTitle) }} @lang('to') {{ __($general->sitename) }} @lang('operator dashboard')</p>
                <form action="{{ route('operator.password.update') }}" method="POST" class="cmn-form mt-30">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="form-group">
                        <label for="password">@lang('New Password')</label>
                        <input type="password" name="password"
                            class="form-control b-radius--capsule @error('password') is-invalid @enderror" id="password"
                            placeholder="@lang('Enter new password')" required>
                        <i class="las la-lock input-icon"></i>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">@lang('Confirm New Password')</label>
                        <input type="password" name="password_confirmation"
                            class="form-control b-radius--capsule @error('password_confirmation') is-invalid @enderror"
                            id="password_confirmation" placeholder="@lang('Confirm new password')" required>
                        <i class="las la-lock input-icon"></i>
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <button type="submit" class="submit-btn mt-25 b-radius--capsule">
                            @lang('Reset Password') <i class="las la-key"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-muted">@lang('Remember your password?') <a href="{{ route('operator.login') }}"
                            class="text--base">@lang('Login')</a></p>
                </div>
            </div>
        </div><!-- login-area end -->
    </div>
@endsection
