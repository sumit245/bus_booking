@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper default-version">
        <div class="form-area bg_img" data-background="{{ asset('assets/admin/images/1.jpg') }}">
            <div class="form-wrapper">
                <h4 class="logo-text mb-15">@lang('Welcome to') <strong>{{ __($general->sitename) }}</strong></h4>
                <p>{{ __($pageTitle) }} @lang('to') {{ __($general->sitename) }} @lang('operator dashboard')</p>
                <form action="{{ route('operator.password.verify.code') }}" method="POST" class="cmn-form mt-30">
                    @csrf
                    <div class="form-group">
                        <label for="code">@lang('Verification Code')</label>
                        <input type="text" name="code"
                            class="form-control b-radius--capsule @error('code') is-invalid @enderror" id="code"
                            value="{{ old('code') }}" placeholder="@lang('Enter verification code')" required>
                        <i class="las la-key input-icon"></i>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="submit-btn mt-25 b-radius--capsule">
                            @lang('Verify Code') <i class="las la-check"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-muted">@lang('Didn\'t receive code?') <a href="{{ route('operator.password.reset') }}"
                            class="text--base">@lang('Try Again')</a></p>
                </div>
            </div>
        </div><!-- login-area end -->
    </div>
@endsection
