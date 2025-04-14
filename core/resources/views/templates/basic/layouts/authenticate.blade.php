<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.seo')
    <!-- BootStrap Link -->
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/bootstrap.min.css') }}">

    <!-- Icon Link -->
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/flaticon.css') }}">

    <!-- Plugings Link -->
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/slick.css') }}">
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/jquery-ui.css') }}">

    <!-- Custom Link -->
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/main.css') }}">
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue.'css/color.php?color='.$general->base_color)}}">

    <title>{{ $general->sitename(__($pageTitle)) }}</title>
    @stack('style')
</head>
<body>
    <div class="overlay"></div>
    <!-- Preloader -->
    <div class="preloader">
        <div class="loader-wrapper">
            <div class="truck-wrapper">
              <div class="truck">
                <div class="truck-container"></div>
                <div class="glases"></div>
                <div class="bonet"></div>

                <div class="base"></div>

                <div class="base-aux"></div>
                <div class="wheel-back"></div>
                <div class="wheel-front"></div>

                <div class="smoke"></div>
              </div>
            </div>
        </div>
    </div>
    <!-- Preloader -->

    @yield('content')

    <script src="{{asset('assets/global/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{asset($activeTemplateTrue.'js/bootstrap.min.js') }}"></script>
    <script src="{{asset($activeTemplateTrue.'js/slick.min.js') }}"></script>
    <script src="{{asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{asset($activeTemplateTrue.'js/jquery-ui.min.js') }}"></script>
    <script src="{{asset($activeTemplateTrue.'js/main.js') }}"></script>
    <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>


    @include('partials.plugins')

    @include('partials.notify')
    @stack('script')
</body>
</html>
