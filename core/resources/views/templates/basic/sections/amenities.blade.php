@php
    $amenitiesContent = getContent('amenities.content', true);
    $facilities = getContent('amenities.element',false,null,true);
@endphp
<!-- Our Ameninies Section Starts Here -->
<section class="amenities-section padding-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-10">
                <div class="section-header text-center">
                    <h2 class="title">{{ __(@$amenitiesContent->data_values->heading) }}</h2>
                    <p>{{ __(@$amenitiesContent->data_values->sub_heading) }}</p>
                </div>
            </div>
        </div>
        <div class="amenities-wrapper">
            <div class="amenities-slider">
                @foreach ($facilities as $item)
                <div class="single-slider">
                    <div class="amenities-item">
                        <div class="thumb">
                            @php
                                echo $item->data_values->icon
                            @endphp
                        </div>
                        <h6 class="title">{{ __($item->data_values->title) }}</h6>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
<!-- Our Ameninies Section Ends Here -->
