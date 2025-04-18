@extends($activeTemplate . $layout)
@php
  use Illuminate\Support\Facades\DB;
  $cities = DB::table("cities")->get();
  $originCity = DB::table("cities")->where("city_id", request()->OriginId)->first();
  $destinationCity = DB::table("cities")->where("city_id", request()->DestinationId)->first();
  $SearchTokenId = session()->get("search_token_id", null);
@endphp

@section("content")
  <div class="ticket-search-bar bg_img padding-top"
    style="background: url({{ getImage("assets/templates/basic/images/bg/inner.jpg") }}) left center;">
    <div class="container">
      <div class="bus-search-header">
        <form action="{{ route("search") }}" class="ticket-form ticket-form-two row g-3 justify-content-center">
          <div class="col-md-4 col-lg-3">
            <div class="form--group">
              <i class="las la-location-arrow"></i>
              <input type="text" disabled id="origin-id" name="OriginId" class="form--control"
                value="{{ $originCity->city_name }}">
            </div>
          </div>

          <div class="col-md-4 col-lg-3">
            <div class="form--group">
              <i class="las la-map-marker"></i>
              <input type="text" disabled id="destination-id" class="form--control" name="DestinationId"
                value="{{ $destinationCity->city_name }}">
            </div>
          </div>

          <div class="col-md-4 col-lg-3">
            <div class="form--group">
              <i class="las la-calendar-check"></i>
              <input type="text" name="date_of_journey" class="form--control datpicker" placeholder="@lang("Date of Journey")"
                autocomplete="off" value="{{ request()->DateOfJourney }}">
            </div>
          </div>

          <div class="col-md-6 col-lg-3">
            <div class="form--group">
              <button>@lang("Find Tickets")</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Ticket Section Starts Here -->
  <section class="ticket-section padding-bottom section-bg">
    <div class="container">
      <div class="row gy-5">
        <div class="col-lg-3">
          <form action="{{ route("search") }}" id="filterFordsm">
            <div class="ticket-filter">
              <div class="filter-header filter-item">
                <h4 class="title mb-0">@lang("Filter")</h4>
                <button type="reset" class="reset-button h-auto">@lang("Reset All")</button>
              </div>

              @if ($fleetType)
                <div class="filter-item">
                  <h5 class="title">@lang("Vehicle Type")</h5>
                  <ul class="bus-type">
                    @foreach ($fleetType as $fleet)
                      <li class="custom--checkbox">
                        <input name="fleetType[]" class="search" value="{{ $fleet->id }}" id="{{ $fleet->name }}"
                          type="checkbox" {{ in_array($fleet->id, request()->fleetType ?? []) ? "checked" : "" }}>
                        <label for="{{ $fleet->name }}"><span><i
                              class="las la-bus"></i>{{ __($fleet->name) }}</span></label>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif

              @if ($routes)
                <div class="filter-item">
                  <h5 class="title">@lang("Routes")</h5>
                  <ul class="bus-type">
                    @foreach ($routes as $route)
                      <li class="custom--checkbox">
                        <input name="routes[]" class="search" value="{{ $route->id }}" id="route.{{ $route->id }}"
                          type="checkbox" {{ in_array($route->id, request()->routes ?? []) ? "checked" : "" }}>
                        <label for="route.{{ $route->id }}"><span><i
                              class="las la-road"></i>{{ __($route->name) }}</span></label>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif

              @if ($schedules)
                <div class="filter-item">
                  <h5 class="title">@lang("Schedules")</h5>
                  <ul class="bus-type">
                    @foreach ($schedules as $schedule)
                      <li class="custom--checkbox">
                        <input name="schedules[]" class="search" value="{{ $schedule->id }}"
                          id="schedule.{{ $schedule->id }}" type="checkbox"
                          {{ in_array($schedule->id, request()->schedules ?? []) ? "checked" : "" }}>
                        <label for="schedule.{{ $schedule->id }}"><span><i
                              class="las la-clock"></i>{{ showDateTime($schedule->start_from, "h:i a") . " - " . showDateTime($schedule->end_at, "h:i a") }}</span></label>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </div>
          </form>
        </div>

        <div class="col-lg-9">
          <div class="ticket-wrapper">
            @forelse ($trips as $trip)
              <div class="ticket-item">
                <div class="ticket-item-inner">
                  <h5 class="bus-name">{{ __($trip["TravelName"]) }}</h5>
                  <span class="bus-info">@lang("Bus Type - ") {{ __($trip["BusType"]) }}</span>
                  <span class="ratting"><i class="las la-bus"></i>{{ __($trip["ServiceName"]) }}</span>
                </div>
                <div class="ticket-item-inner travel-time">
                  <div class="bus-time">
                    <p class="time">{{ \Carbon\Carbon::parse($trip["DepartureTime"])->format("h:i A") }}</p>
                    <p class="place">{{ __($trip["BoardingPointsDetails"][0]["CityPointLocation"]) }}</p>
                  </div>
                  <div class="bus-time">
                    <i class="las la-arrow-right"></i>
                    <p>
                      {{ \Carbon\Carbon::parse($trip["ArrivalTime"])->diffInHours(\Carbon\Carbon::parse($trip["DepartureTime"])) }}
                      hours</p>
                  </div>
                  <div class="bus-time">
                    <p class="time">{{ \Carbon\Carbon::parse($trip["ArrivalTime"])->format("h:i A") }}</p>
                    <p class="place">{{ __($trip["DroppingPointsDetails"][0]["CityPointLocation"]) }}</p>
                  </div>
                </div>
                <div class="ticket-item-inner book-ticket">
                  <p class="rent mb-0">{{ __($general->cur_sym) }}{{ showAmount($trip["BusPrice"]["PublishedPrice"]) }}
                  </p>
                </div>
                <a class="btn btn--base"
                  href="{{ route("ticket.seats", [$trip["ResultIndex"], slug($trip["TravelName"])]) }}">@lang("Select Seat")</a>
              </div>
            @empty
              <div class="ticket-item">
                <h5>{{ __($emptyMessage) }}</h5>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection

@push("script")
  <script>
    $(document).ready(function() {
      // Configure datepicker to disable past dates
      $('.datpicker').datepicker({
        minDate: new Date(),
        startDate: new Date(),
        maxDate: new Date(new Date().setDate(new Date().getDate() + 100)),
        autoclose: true,
        format: 'yyyy-mm-dd'
      });
    });
    // (function($) {
    //   "use strict";
    //   $('.search').on('change', function() {
    //     $('#filterForm').submit();
    //   });

    //   $('.reset-button').on('click', function() {
    //     $('.search').prop('checked', false);
    //     $('#filterForm').submit();
    //   });
    // })(jQuery);
  </script>
@endpush
