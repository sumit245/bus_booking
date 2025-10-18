<div class="row">
    @foreach ($stoppages as $item)
        @if($item[0] != $item [1])
        @php $sd = getStoppageInfo($item) @endphp
            <div class="col-md-4">
                <label for="point-{{$loop->iteration}}">{{$sd[0]->name}} - {{$sd[1]->name}}</label>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="btn--light input-group-text">{{ $general->cur_sym }}</span>
                    </div>
                    <input type="text" name="price[{{$sd[0]->id}}-{{$sd[1]->id}}]" id="point-{{$loop->iteration}}" class="form-control prices-auto numeric-validation" placeholder="@lang('Enter a price')" required />
                </div>
            </div>
        @endif
    @endforeach
</div>
<script>
    'use strict';
    (function($){
        $(".numeric-validation").keypress(function(e){
        var unicode = e.charCode ? e.charCode : e.keyCode
            if (unicode != 8 && e.key != '.' && unicode != 45) {
                if ((unicode < 2534 || unicode > 2543) && (unicode < 48 || unicode > 57)) {
                    return false;
                }
            }
        });
    })(jQuery)
</script>
