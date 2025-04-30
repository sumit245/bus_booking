@php
  $fleetType = $fleetType ?? [];
  $routes = $routes ?? [];
  $schedules = $schedules ?? [];
  $minPrice = 0; // Always fixed at 0
  $maxPrice = request()->max_price ?? 5000;
@endphp

<div class="ticket-filter">
  <div class="filter-header filter-item">
    <h4 class="title mb-0">@lang("Filter")</h4>
    <button type="reset" class="reset-button h-auto">@lang("Reset All")</button>
  </div>

  {{-- Live tracking filter --}}
  <div class="filter-item">
    <h5 class="title">@lang("Features")</h5>
    <ul class="bus-type">
      <li class="custom--checkbox">
        <input name="live_tracking" class="search" value="1" id="tracking_enabled" type="checkbox"
          {{ request()->live_tracking ? "checked" : "" }}>
        <label for="tracking_enabled"><span><i class="las la-map-marker-alt"></i>@lang("Live Tracking")</span></label>
      </li>
    </ul>
  </div>

  {{-- Bus types filter --}}
  <div class="filter-item">
    <h5 class="title">@lang("Bus Types")</h5>
    <ul class="bus-type">
      <li class="custom--checkbox">
        <input name="fleetType[]" class="search" value="Seater" id="seater" type="checkbox"
          {{ in_array("Seater", request()->fleetType ?? []) ? "checked" : "" }}>
        <label for="seater"><span><i class="las la-bus"></i>@lang("Seater")</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="fleetType[]" class="search" value="Sleeper" id="sleeper" type="checkbox"
          {{ in_array("Sleeper", request()->fleetType ?? []) ? "checked" : "" }}>
        <label for="sleeper"><span><i class="las la-bed"></i>@lang("Sleeper")</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="fleetType[]" class="search" value="A/C" id="ac" type="checkbox"
          {{ in_array("A/C", request()->fleetType ?? []) ? "checked" : "" }}>
        <label for="ac"><span><i class="las la-snowflake"></i>@lang("AC")</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="fleetType[]" class="search" value="Non A/C" id="non-ac" type="checkbox"
          {{ in_array("Non A/C", request()->fleetType ?? []) ? "checked" : "" }}>
        <label for="non-ac"><span><i class="las la-fan"></i>@lang("Non-AC")</span></label>
      </li>
    </ul>
  </div>

  <!-- Departure Time Filter -->
  <div class="filter-item">
    <h5 class="title">@lang("Departure Time")</h5>
    <ul class="bus-type">
      <li class="custom--checkbox">
        <input name="departure_time[]" class="search" value="morning" id="morning" type="checkbox"
          {{ in_array("morning", request()->departure_time ?? []) ? "checked" : "" }}>
        <label for="morning"><span><i class="las la-sun"></i>@lang("Morning") (4AM - 12PM)</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="departure_time[]" class="search" value="afternoon" id="afternoon" type="checkbox"
          {{ in_array("afternoon", request()->departure_time ?? []) ? "checked" : "" }}>
        <label for="afternoon"><span><i class="las la-cloud-sun"></i>@lang("Afternoon") (12PM - 4PM)</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="departure_time[]" class="search" value="evening" id="evening" type="checkbox"
          {{ in_array("evening", request()->departure_time ?? []) ? "checked" : "" }}>
        <label for="evening"><span><i class="las la-cloud-moon"></i>@lang("Evening") (4PM - 8PM)</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="departure_time[]" class="search" value="night" id="night" type="checkbox"
          {{ in_array("night", request()->departure_time ?? []) ? "checked" : "" }}>
        <label for="night"><span><i class="las la-moon"></i>@lang("Night") (8PM - 4AM)</span></label>
      </li>
    </ul>
  </div>

  <!-- Bus Amenities Filter -->
  <div class="filter-item">
    <h5 class="title">@lang("Bus Amenities")</h5>
    <ul class="bus-type">
      <li class="custom--checkbox">
        <input name="amenities[]" class="search" value="wifi" id="wifi" type="checkbox"
          {{ in_array("wifi", request()->amenities ?? []) ? "checked" : "" }}>
        <label for="wifi"><span><i class="las la-wifi"></i>@lang("WiFi")</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="amenities[]" class="search" value="charging" id="charging" type="checkbox"
          {{ in_array("charging", request()->amenities ?? []) ? "checked" : "" }}>
        <label for="charging"><span><i class="las la-charging-station"></i>@lang("Charging Point")</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="amenities[]" class="search" value="water" id="water" type="checkbox"
          {{ in_array("water", request()->amenities ?? []) ? "checked" : "" }}>
        <label for="water"><span><i class="las la-tint"></i>@lang("Water Bottle")</span></label>
      </li>
      <li class="custom--checkbox">
        <input name="amenities[]" class="search" value="blanket" id="blanket" type="checkbox"
          {{ in_array("blanket", request()->amenities ?? []) ? "checked" : "" }}>
        <label for="blanket"><span><i class="las la-bed"></i>@lang("Blanket")</span></label>
      </li>
    </ul>
  </div>

  <!-- Price Range Filter -->
  <div class="filter-item">
    <h5 class="title">@lang("Price Range")</h5>
    <div class="price-range-area">
      <div id="price-slider" class="price-range-slider"></div>
      <div class="price-input-wrapper d-flex justify-content-between mt-2">
        <div class="price-input">
          <label>@lang("Min")</label>
          <input type="text" id="min-price" name="min_price" value="{{ $minPrice }}" readonly>
        </div>
        <div class="price-input">
          <label>@lang("Max")</label>
          <input type="text" id="max-price" name="max_price" value="{{ $maxPrice }}" readonly>
        </div>
      </div>
    </div>
  </div>
</div>

@push("style")
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.1/nouislider.min.css">
  <style>
    .noUi-connect {
      background: var(--primary-color, #5A5278);
    }

    .noUi-horizontal {
      height: 8px;
    }

    .noUi-horizontal .noUi-handle {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      top: -7px;
      cursor: pointer;
      background: var(--primary-color, #5A5278);
      border: none;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    }

    .noUi-handle:before,
    .noUi-handle:after {
      display: none;
    }

    .noUi-origin:first-child {
      pointer-events: none;
    }

    .price-input {
      width: 35%;
    }
  </style>
@endpush

@push("script")
  <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.1/nouislider.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const priceSlider = document.getElementById('price-slider');
      const minPriceInput = document.getElementById('min-price');
      const maxPriceInput = document.getElementById('max-price');
      const initialMaxPrice = parseInt(maxPriceInput.value) || 5000;

      if (priceSlider) {
        noUiSlider.create(priceSlider, {
          start: [0, initialMaxPrice],
          connect: true,
          behaviour: 'tap-drag',
          step: 50,
          range: {
            'min': 0,
            'max': 5000
          },
          format: {
            to: function(value) {
              return Math.round(value);
            },
            from: function(value) {
              return Number(value);
            }
          }
        });
        priceSlider.noUiSlider.on('update', function(values, handle) {
          if (handle === 0) {
            minPriceInput.value = 0;
          } else {
            maxPriceInput.value = values[1];
          }
        });
        const resetButton = document.querySelector('.reset-button');
        if (resetButton) {
          resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            priceSlider.noUiSlider.set([0, 5000]);
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
              checkbox.checked = false;
            });
          });
        }
        const handles = priceSlider.querySelectorAll('.noUi-handle');
        if (handles.length > 0) {
          handles[0].style.display = 'none';
        }
      }
    });
  </script>
@endpush
