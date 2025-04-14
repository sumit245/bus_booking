@extends($activeTemplate . $layout)
@section("content")
@php
use Illuminate\Support\Facades\DB;
$cities = DB::table("cities")->get();
$originCity = DB::table("cities")
->where("city_id", session()->get("origin_id", "default"))
->first();
$destinationCity = DB::table("cities")
->where("city_id", session()->get("destination_id", "default"))
->first();
@endphp
<!-- SECTION: BOOKING OVERVIEW -->
<div class="padding-top padding-bottom">
    <div class="container">
        <div class="row gx-xl-5 gy-4 gy-sm-5 justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="seat-overview-wrapper">
                    <form action="" method="POST" id="bookingForm" class="row gy-2">
                        @csrf
                        <input type="text" name="price" hidden>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="date_of_journey" class="form-label">@lang("Journey Date")</label>
                                <input type="text" id="date_of_journey" class="form--control datepicker"
                                    value="{{ Session::get("date_of_journey") ? Session::get("date_of_journey") : date("m/d/Y") }}"
                                    name="date_of_journey">
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="pickup_point" class="form-label">@lang("Pickup Point")</label>
                            <div class="form--group">
                                <i class="las la-map-marker"></i>
                                <input type="text" disabled id="pickup-id" class="form--control" name="pickupId"
                                    value="{{ $originCity->city_name }}" id="pickup-point">
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="dropping_point" class="form-label">@lang("Dropping Point")</label>
                            <div class="form--group">
                                <i class="las la-map-marker"></i>
                                <input type="text" disabled id="destination-id" class="form--control" name="DestinationId"
                                    value="{{ $destinationCity->city_name }}">
                            </div>
                        </div>
                        {{-- Select Gender --}}
                        <div class="col-12">
                            <label class="form-label">@lang("Select Gender")</label>
                            <div class="d-flex justify-content-between flex-wrap">
                                <div class="form-group custom--radio">
                                    <input id="male" type="radio" name="gender" value="1">
                                    <label class="form-label" for="male">@lang("Male")</label>
                                </div>
                                <div class="form-group custom--radio">
                                    <input id="female" type="radio" name="gender" value="2">
                                    <label class="form-label" for="female">@lang("Female")</label>
                                </div>
                                <div class="form-group custom--radio">
                                    <input id="other" type="radio" name="gender" value="3">
                                    <label class="form-label" for="other">@lang("Other")</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-none">
                            <label for="passengers" class="form-label">@lang("Passengers")</label>
                            <div class="form--group">
                                <input type="tel" name="passengers" class="form--control" min="1" max="10"
                                    placeholder="@lang(" Mobile Number")">
                            </div>
                        </div>

                        <div class="booked-seat-details d-none my-3">
                            <label>@lang("Selected Seats")</label>
                            <div class="list-group seat-details-animate">
                                <span
                                    class="list-group-item d-flex bg--base justify-content-between text-white">@lang("Seat Details")<span>@lang("Price")</span></span>
                                <div class="selected-seat-details">
                                </div>
                            </div>
                        </div>
                        <input type="text" name="seats" hidden>
                        <div class="col-12">
                            <button type="submit" class="book-bus-btn">@lang("Continue")</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Right column with seat layout -->
            <div class="col-lg-4 col-md-6">
                <h6 class="title">@lang("Click on Seat to select or deselect")</h6>
                {{-- TODO: Add seat layout here --}}
                @php
                $busLayout = new App\Http\Helpers\GenerateSeatLayout($seatLayout);
                echo $busLayout->generateLayout();
                @endphp

                {{-- <div class="seat-layout-container">
            {!! $seatHtml !!}
          </div> --}}
                <div class="seat-for-reserved">
                    <div class="seat-condition available-seat">
                        <span class="seat"><span></span></span>
                        <p>@lang("Available Seats")</p>
                    </div>
                    <div class="seat-condition selected-by-you">
                        <span class="seat"><span></span></span>
                        <p>@lang("Selected by You")</p>
                    </div>
                    <div class="seat-condition selected-by-gents">
                        <div class="seat"><span></span></div>
                        <p>@lang("Booked by Gents")</p>
                    </div>
                    <div class="seat-condition selected-by-ladies">
                        <div class="seat"><span></span></div>
                        <p>@lang("Booked by Ladies")</p>
                    </div>
                    <div class="seat-condition selected-by-others">
                        <div class="seat"><span></span></div>
                        <p>@lang("Booked by Others")</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--SECTION: End of booking section -->
@endsection
