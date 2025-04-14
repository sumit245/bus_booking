@php
  use Illuminate\Support\Facades\DB;
  $contents = getContent("banner.content", true);
  $counters = App\Models\Counter::get();
  $cities = DB::table("cities")->get();
@endphp

<!-- Banner Section Starts Here -->
<section class="banner-section"
  style="background: url({{ getImage("assets/images/frontend/banner/" . $contents->data_values->background_image, "1500x88") }}) repeat-x bottom;">
  <div class="container">
    <div class="banner-wrapper">
      <div class="banner-content">
        <h1 class="title">{{ __($contents->data_values->heading) }}</h1>
        <a href="{{ __(@$contents->data_values->link) }}"
          class="cmn--btn">{{ __(@$contents->data_values->link_title) }}</a>
      </div>
      <div class="ticket-form-wrapper mt-4">
        <form action="{{ route("search") }}" class="ticket-form row g-3 justify-content-center mt-4 pt-4">
          <h4 class="title my-4">@lang("Choose Your Ticket")</h4>
          <div class="col-md-6 my-2">
            <div class="form--group">
              <i class="las la-location-arrow"></i>
              <input type="hidden" id="origin-id" name="OriginId" value="{{ request()->OriginId }}">
              <input type="text" id="origin" class="form--control" placeholder="@lang("From")"
                autocomplete="off">
              <div id="autocomplete-list-origin" class="autocomplete-items"></div>
            </div>
          </div>
          <div class="col-md-6 my-2">
            <div class="form--group">
              <i class="las la-map-marker"></i>
              <input type="hidden" id="destination-id" name="DestinationId" value="{{ request()->DestinationId }}">
              <input type="text" id="destination" class="form--control" placeholder="@lang("To")"
                autocomplete="off">
              <div id="autocomplete-list-destination" class="autocomplete-items"></div>
            </div>
          </div>
          <div class="col-md-12 my-2">
            <div class="form--group">
              <i class="las la-calendar-check"></i>
              <input type="text" name="DateOfJourney" class="form--control datpicker" placeholder="@lang("Departure Date")"
                autocomplete="off">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form--group">
              <button>@lang("Find Tickets")</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="shape">
    <img src="{{ getImage("assets/images/frontend/banner/" . $contents->data_values->animation_image, "200x69") }}"
      alt="bg">
  </div>
</section>
<!-- Banner Section Ends Here -->

@push("script")
  <script>
    $(document).ready(function() {
      $('.datpicker').datepicker({
        minDate: new Date(),
        startDate: new Date(),
        maxDate: new Date(new Date().setDate(new Date().getDate() + 100)),
        autoclose: true,
        format: 'yyyy-mm-dd'
      });
      const cities = @json($cities); // Pass the cities array to JavaScript

      function setupAutocomplete(inputId, listId, hiddenId) {
        $(`#${inputId}`).on('input', function() {
          const input = $(this).val().toLowerCase();
          $(`#${listId}`).empty(); // Clear previous suggestions

          if (input.length === 0) return; // If input is empty, do nothing

          // Filter cities based on input
          const filteredCities = cities.filter(city => {
            const cityName = city.city_name.toLowerCase();
            return cityName.includes(input);
          });

          // Sort filtered cities to prioritize exact matches
          filteredCities.sort((a, b) => {
            const aName = a.city_name.toLowerCase();
            const bName = b.city_name.toLowerCase();
            return aName === input ? -1 : (bName === input ? 1 : 0);
          });

          // Create autocomplete suggestions
          filteredCities.forEach(city => {
            $(`#${listId}`).append(`
            <div class="autocomplete-item" data-id="${city.city_id}">${city.city_name}</div>
          `);
          });
        });

        // Handle click on autocomplete item
        $(document).on('click', `#${listId} .autocomplete-item`, function() {
          const cityId = $(this).data('id');
          const cityName = $(this).text();
          $(`#${inputId}`).val(cityName); // Set the input value
          $(`#${hiddenId}`).val(cityId); // Set the hidden input value
          $(`#${listId}`).empty(); // Clear suggestions
        });

        // Close the autocomplete list when clicking outside
        $(document).on('click', function(e) {
          if (!$(e.target).closest(`#${inputId}`).length) {
            $(`#${listId}`).empty();
          }
        });
      }

      // Setup autocomplete for origin and destination
      setupAutocomplete('origin', 'autocomplete-list-origin', 'origin-id');
      setupAutocomplete('destination', 'autocomplete-list-destination', 'destination-id');
    });
  </script>
@endpush

@push("style")
  <style>
    .autocomplete-items {
      overflow-y: auto;
      /* Enable vertical scrolling */
      max-height: 200px;
      /* Set a max height for the suggestions */
      position: absolute;
      border: 1px solid #d4d4d4;
      border-bottom: none;
      border-top: none;
      z-index: 99;
      top: 100%;
      left: 0;
      right: 0;
      background-color: #fff;
      /* Ensure background is white */
    }

    .autocomplete-item {
      padding: 10px;
      cursor: pointer;
      background-color: #fff;
    }

    .autocomplete-item:hover {
      background-color: #e9e9e9;
    }
  </style>
@endpush
