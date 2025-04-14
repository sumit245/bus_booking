@extends($activeTemplate .'layouts.frontend')
@section('content')
<div class="container padding-top padding-bottom">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-6">
            <div class="profile__content__edit p-0">
                <div class="title">@lang('Please Verify Your Email to Get Access')</div>
                <div class="p-4">
                    <form action="{{route('user.verify.email')}}" method="POST" class="login-form">
                        @csrf

                        <div class="form-group">
                            <p class="text-center">@lang('Your Email'):  <strong>{{auth()->user()->email}}</strong></p>
                        </div>

                        <div class="input-group">
                            <label for="code" class="form-label">@lang('Verification Code')</label>
                            <input type="text" class="form-contorl form--control radius-0" id="code" name="email_verified_code" maxlength="7">
                        </div>

                        <div class="col">
                            <button type="submit" class="btn btn--base btn--block mt-3">@lang('Submit')</button>
                        </div>
                        <div class="form-group">
                            <small>@lang('Please check including your Junk/Spam Folder. if not found, you can') <a class="btn btn-link" href="{{route('user.send.verify.code')}}?type=email" class="forget-pass"> @lang('Resend code')</a></small>
                            @if ($errors->has('resend'))
                                <br/>
                                <small class="text-danger">{{ $errors->first('resend') }}</small>
                            @endif
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
    (function($){
        "use strict";
        $('#code').on('input change', function () {
          var xx = document.getElementById('code').value;

              $(this).val(function (index, value) {
                 value = value.substr(0,7);
                  return value.replace(/\W/gi, '').replace(/(.{3})/g, '$1 ');
              });

      });
    })(jQuery)
</script>
@endpush
