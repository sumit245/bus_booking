@extends($activeTemplate.'layouts.authenticate')
@section('content')
    @php
        $content = getContent('reset_password.content', true);
    @endphp
    <!-- Account Section Starts Here -->
    <section class="account-section bg_img" style="background: url({{getImage('assets/images/frontend/reset_password/'. @$content->data_values->background_image, "1920x1280") }}) bottom left;">
        <div class="account-wrapper">
            <div class="account-form-wrapper">
                <div class="account-header">
                    <div class="left-content">
                        <div class="logo mb-4">
                            <a href="{{ route('home') }}"><img src="{{ getImage(imagePath()['logoIcon']['path'].'/logo.png') }}" alt="Logo"></a>
                        </div>
                         <h3 class="title">@lang('Reset Password')</h3>
                    </div>
                </div>

                <form class="contact-form row gy-3" method="POST" action="{{ route('user.password.email') }}">
                    @csrf
                    <div class="col-xl-12">
                        <div class="form--group">
                            <label for="type">@lang('Select One')</label>
                            <select class="form--control" name="type" id="type">
                                <option value="email">@lang('E-Mail Address')</option>
                                <option value="username">@lang('Username')</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-12">
                        <div class="form--group">
                            <label for="value"  class="my_value"></label>
                            <input type="text" class="form--control @error('value') is-invalid @enderror" name="value" value="{{ old('value') }}" required autofocus="off">

                            @error('value')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form--group">
                            <button type="submit" class="contact-button">@lang('Send Password Code')</button>
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
        (function($){
            "use strict";

            myVal();
            $('select[name=type]').on('change',function(){
                myVal();
            });
            function myVal(){
                $('.my_value').text($('select[name=type] :selected').text());
            }
        })(jQuery)
    </script>
@endpush
