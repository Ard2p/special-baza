<a href="{{$machine->rent_url}}">
    <h2 style="    margin: 15px;">{{$machine->_type->name}} {{$machine->brand->name ?? ''}} <p
                style="font-size: 15px;">{{$machine->city->name ?? ''}}, {{$machine->region->name ?? ''}}</p></h2>
</a>

<div class="col-md-12">

    <div style="max-width: 450px; margin: 0 auto;">
        @include('user.machines.slider')
{{--        <a class="thumbnail fancybox" rel="ligthbox"
           href="{{$machine->rent_url}}">
            <img alt="{{$machine->_type->name}} {{$machine->brand->name ?? ''}} {{$machine->city->name ?? ''}}, {{$machine->region->name ?? ''}}"
                 src="/{{$machine->category_image}}" class="img-responsive"
                 style="height: auto; width: auto"></a>--}}
    </div>
    <div class="list-params">
        @include('special.machine_params')

    </div>
    <div class="form-item">
        <div class="button">
            <a class="btn-custom"
               href="{{$machine->rent_url}}">@lang('transbaza_spectehnika.rent_page_link')
            </a>
        </div>
    </div>
</div>