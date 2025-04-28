@php
  $fleetType = $fleetType ?? [];
  $routes = $routes ?? [];
  $schedules = $schedules ?? [];
@endphp

<div class="ticket-filter">
  <div class="filter-header filter-item">
    <h4 class="title mb-0">@lang("Filter")</h4>
    <button type="reset" class="reset-button h-auto">@lang("Reset All")</button>
  </div>

  <ul class="bus-type">
    <li class="custom--checkbox">
      <input name="fleetType[]" class="search" value="" id="" type="checkbox">
      <label for=""><span><i class="las la-bus"></i>Live Tracking</span></label>
    </li>
  </ul>
  @if ($fleetType)
    <div class="filter-item">
      <h5 class="title">@lang("Bus Types")</h5>
      <ul class="bus-type">
        <li class="custom--checkbox">
          <input name="fleetType[]" class="search" value="" id="" type="checkbox">
          <label for=""><span><i class="las la-bus"></i>Seater</span></label>
        </li>
        <li class="custom--checkbox">
          <input name="fleetType[]" class="search" value="" id="" type="checkbox">
          <label for=""><span><i class="las la-bus"></i>Sleeper</span></label>
        </li>
        <li class="custom--checkbox">
          <input name="fleetType[]" class="search" value="" id="" type="checkbox">
          <label for=""><span><i class="las la-bus"></i>AC</span></label>
        </li>
        <li class="custom--checkbox">
          <input name="fleetType[]" class="search" value="" id="" type="checkbox">
          <label for=""><span><i class="las la-bus"></i>Non-AC</span></label>
        </li>
      </ul>
    </div>
  @endif

  <!-- Departure Time Filter -->
  <div class="filter-item">
    <h5 class="title">@lang("Departure Time")</h5>
    <ul class="bus-type">
      <li class="custom--checkbox">
        <input name="departure_time[]" class="search" value="morning" id="morning" type="checkbox"
          {{ in_array("morning", request()->departure_time ?? []) ? "checked" : "" }}>
        <label for="morning"><span><i class="las la-sun"></i>@lang("Morning") (6AM - 12PM)</span></label>
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
        <label for="night"><span><i class="las la-moon"></i>@lang("Night") (8PM - 6AM)</span></label>
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
      <div class="price-range-slider"></div>
      <div class="price-input-wrapper d-flex justify-content-between mt-2">
        <div class="price-input">
          <label>@lang("Min")</label>
          <input type="text" id="min-price" name="min_price" value="{{ request()->min_price ?? 0 }}">
        </div>
        <div class="price-input">
          <label>@lang("Max")</label>
          <input type="text" id="max-price" name="max_price" value="{{ request()->max_price ?? 5000 }}">
        </div>
      </div>
    </div>
  </div>

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
            <input name="schedules[]" class="search" value="{{ $schedule->id }}" id="schedule.{{ $schedule->id }}"
              type="checkbox" {{ in_array($schedule->id, request()->schedules ?? []) ? "checked" : "" }}>
            <label for="schedule.{{ $schedule->id }}"><span><i
                  class="las la-clock"></i>{{ showDateTime($schedule->start_from, "h:i a") . " - " . showDateTime($schedule->end_at, "h:i a") }}</span></label>
          </li>
        @endforeach
      </ul>
    </div>
  @endif
</div>
