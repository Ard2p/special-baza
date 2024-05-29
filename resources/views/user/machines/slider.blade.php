<div class="col-md-12">
    <div class="main-slider" style="/*border-left: 2px solid #ed5a43;*/text-align: center">
        @php
        $allowed_routes = [
        'directory_main_category',
        'directory_main_result',
        'directory_main_region',
        'contractor_public_page',
        ];
        $route = Route::currentRouteName();

        @endphp
        {{--@if(in_array($route, $allowed_routes) && $machine->photos[0] !== $machine->category_image)
            <a href="/{{$machine->category_image}}" class="fancybox" data-fancybox="images">
                <img src="/{{$machine->category_image}}" alt="{{$machine->_type->name}} {{$machine->brand->name ?? ''}}" style="max-height: 250px;" />
            </a>
        @endif--}}

        @foreach($machine->photos as $k => $photo)
            <a href="/{{$photo}}" class="fancybox" data-fancybox="images">
                <img src="/{{$photo}}" alt="{{$machine->_type->name}} {{$machine->brand->name ?? ''}} {{$machine->city->name ?? ''}}, {{$machine->region->name ?? ''}}, {{$k}}" style="max-height: 250px;  padding: 28px; {{$route === 'contractor_public_page'}}"/>
            </a>
        @endforeach
    </div>
</div>
