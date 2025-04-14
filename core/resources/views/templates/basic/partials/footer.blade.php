@php
$content = getContent('footer.content', true);
$socialLinks = getContent('social_links.element',false,null,true);
$policies = getContent('policies.element',false,null,true);
@endphp
<!-- Footer Section Starts Here -->
<section class="footer-seciton">
    <div class="footer-top">
        <div class="container">
            <div class="row footer-wrapper gy-sm-5 gy-4">
                <div class="col-xl-4 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <div class="logo">
                            <img src="{{ getImage(imagePath()['logoIcon']['path'].'/logo_2.png') }}" alt="@lang('Logo')">
                        </div>
                        <p>{{ __(@$content->data_values->short_description) }}</p>
                        <ul class="social-icons">
                            @foreach ($socialLinks as $item)
                            <li>
                                <a href="{{ $item->data_values->url }}">@php echo $item->data_values->icon @endphp</a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="footer-widget">
                        <h4 class="widget-title">@lang('Useful Links')</h4>
                        <ul class="footer-links">
                            @foreach($pages as $k => $data)
                            <li>
                                <a href="{{route('pages',[$data->slug])}}">{{__($data->name)}}</a>
                            </li>
                            @endforeach
                            <li>
                                <a href="{{ route('blog') }}">@lang('Blog')</a>
                            </li>
                            <li>
                                <a href="{{ route('contact') }}">@lang('Contact')</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="footer-widget">
                        <h4 class="widget-title">@lang('Policies')</h4>
                        <ul class="footer-links">
                            @foreach ($policies as $policy)
                            <li>
                                <a href="{{ route('policy.details', [$policy->id, slug($policy->data_values->title)]) }}">@php
                                    echo $policy->data_values->title
                                    @endphp</a>
                            </li>
                            @endforeach

                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                    <div class="footer-widget">
                        <h4 class="widget-title">@lang('Contact Info')</h4>
                        @php
                        $contacts = getContent('contact.content', true);
                        @endphp
                        <ul class="footer-contacts">
                            <li>
                                <i class="las la-map-pin"></i> {{ __($contacts->data_values->address) }}
                            </li>
                            <li>
                            <i class="las la-phone-volume"></i> <a href="tel:{{ __($contacts->data_values->contact_number) }}"> {{ __($contacts->data_values->contact_number) }}</a>
                            </li>
                            <li>
                            <i class="las la-envelope"></i> <a href="mailto:{{ __($contacts->data_values->email) }}"> {{ __($contacts->data_values->email) }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Footer Section Ends Here -->

@php
$cookie = App\Models\Frontend::where('data_keys','cookie.data')->first();
@endphp

<!-- cookies default start -->
<div id="cookiePolicy" class="cookies-card bg--default radius--10px text-center">
    <div class="cookies-card__icon">
        <i class="fas fa-cookie-bite"></i>
    </div>
    <p class="mt-4 cookies-card__content">
        @php
        echo @$cookie->data_values->description
        @endphp
        <a href="{{ route('cookie.details') }}" target="_blank">@lang('learn more')</a>
    </p>
    <div class="cookies-card__btn mt-4">
        <a href="#" name="cookieAccept" class="cookies-btn">@lang('Allow')</a>
    </div>
</div>
<!-- cookies default end -->
@push('script')
<script>
    (function($) {
        "use strict";

        $('#cookiePolicy').hide();
        @if(@$cookie-> data_values-> status && !session('cookie_accepted'))
        $('#cookiePolicy').show();
        @endif

        $('a[name="cookieAccept"]').click(function(event) {
            event.preventDefault();
            var actionUrl = "{{ route('cookie.accept') }}";
            $.ajax({
                type: "GET",
                url: actionUrl,
                success: function(data) {
                    console.log(data);
                    $('#cookiePolicy').hide();
                    if (data.success) {
                        notify('success', data.success);
                        $('#cookiePolicy').hide();
                    }
                }
            });
        });
        $('.search').on('change', function() {
            $('#filterForm').submit();
        });
    })(jQuery);
</script>
@endpush
