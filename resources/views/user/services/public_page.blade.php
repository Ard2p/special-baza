@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <form action="" class="machine-card" method="GET">
                <h1 class="text-center">#{{$user->id ?? ''}} БЫСТРЫЙ ЗАКАЗ УСЛУГИ</h1>
                <div class="search-wrap machineries-wrap row" style="padding: 0">

                    <div class="col-md-12 harmony-accord">
                        <h3>Параметры списка</h3>
                        <div class="list-params">
                            <div class="machinery-filter-wrap">
                                <div class="tree-cols-list">
                                    <div class="col">
                                        <helper-select-input :data="{{$types->toJson()}}"
                                                             :column-name="{{json_encode('Категория услуги')}}"
                                                             :place-holder="{{json_encode('Выберите категорию')}}"
                                                             :col-name="{{json_encode('type_id')}}"
                                                             :depend="1"
                                                             :types-url="{{json_encode(['url' => 'api/get-user-service-regions'])}}"
                                                             :user-id="{{$user->id ?? 0}}"

                                                             :initial="{{json_encode($initial_type ?? '')}}"
                                                             :show-column-name="1"></helper-select-input>
                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{$regions->toJson()}}"
                                                             :column-name="{{json_encode('Регион')}}"
                                                             :place-holder="{{json_encode('Выберите регион')}}"
                                                             :col-name="{{json_encode('region')}}"
                                                             :required="0"

                                                             :user-id="{{$user->id ?? 0}}"
                                                             :cities="{{json_encode(['url' => 'api/get-user-service-cities'])}}"

                                                             :types-region-url="{{json_encode(['url' => 'api/get-user-service-types'])}}"
                                                             :depend="1"

                                                             :initial="{{json_encode($initial_region ?? '')}}"
                                                             :initial-city="{{json_encode($checked_city_source ?? '')}}"
                                                             :city-data="{{json_encode([])}}"
                                                             :show-column-name="1"
                                                             :hide-city="1">
                                        </helper-select-input>

                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{json_encode([])}}"
                                                             :column-name="{{json_encode('Город')}}"
                                                             :place-holder="{{json_encode('Город')}}"
                                                             :required="0"

                                                             :depend="1"
                                                             :user-id="{{$user->id ?? 0}}"
                                                             :col-name="{{json_encode('city_id')}}"
                                                             :initial="{{json_encode($checked_city_source ?? '')}}"
                                                             :show-column-name="1"
                                                             :hide-city="1"></helper-select-input>

                                    </div>

                                </div>
                                <div class="tree-cols-list">
                                    <div class="col">
                                        <div class="form-item">
                                            <div class="button">
                                                <button class="btn-custom black" type="submit">Поиск</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div id="machine_show">
            @seoTop
            @contactTop
            <div class="col-md-12">
                @if(!$user)
                    <div class="text-center">
                        {{$services->links( "pagination::bootstrap-4")}}
                    </div>
                @endif
                @if($services->count())
                    @foreach($services as $service)

                        <div class="machine-card">
                            <div class="row">
                                <div>
                                    <a href="{!! $service->rent_url !!}">
                                        <h2 style="    margin: 15px;">{{$service->category->name}} <p
                                                    style="font-size: 15px;">{{$service->city->name ?? ''}}
                                                , {{$service->region->name ?? ''}}</p></h2>
                                    </a>
                                </div>

                                <div class="col-md-6  proposal-wrap ">
                                    <div class="image-wrap">
                                        <a class="thumbnail fancybox" rel="ligthbox"
                                           href="{!! $service->rent_url !!}">
                                            <img alt="{{$service->category->name}}  {{$service->city->name ?? ''}}, {{$service->region->name ?? ''}}"
                                                 src="/{{$service->photo}}" class="img-responsive"
                                                 style="max-height: 400px;"></a>
                                        <input id="profile-image-upload" class="hidden" type="file">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="list-params">
                                        @include('user.services.directory.list_attributes')

                                    </div>
                                    <div class="form-item">
                                        <div class="button">
                                            <a class="btn-custom"
                                               href="{!! $service->rent_url !!}">Заказать
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="margin-wrap"></div>
                    @endforeach
                @else
                    <h4>Ничего не найдено</h4>
                @endif
                @if(!$user)
                    {{$services->links()}}
                @endif
            </div>
            @seoBottom
            @contactBottom
        </div>


    </div>
    {!! \App\Marketing\ShareList::renderShare() !!}
    @push('after-scripts')
        <script>

            $('[data-toggle="_datepicker"]').datetimepicker({
                format: 'Y/m/d',
                dayOfWeekStart: 1,
                timepicker: false
            });

        </script>
        <style>
            .thumbnail {
                margin-bottom: 0px;
            }
        </style>
    @endpush
    @include('scripts.machine.show')
@endsection