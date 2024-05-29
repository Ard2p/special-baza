@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <form action="" class="machine-card" method="GET">
                <h1 class="text-center">#{{$user->id ?? ''}} ОБЪЯВЛЕНИЯ</h1>
                <div class="search-wrap machineries-wrap row" style="padding: 0">

                    <div class="col-md-12 harmony-accord">
                        <h3>Параметры списка</h3>
                        <div class="list-params">
                            <div class="machinery-filter-wrap">
                                <div class="tree-cols-list">
                                    <div class="col">
                                        <helper-select-input :data="{{$types->toJson()}}"
                                                             :column-name="{{json_encode('Категория объявления')}}"
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
            <div id="machine_show">
                @seoTop
                @contactTop
                <div>
                    @if(!$user)
                        <div class="text-center">
                            {{$adverts->links( "pagination::bootstrap-4")}}
                        </div>
                    @endif
                    @if($adverts->count())
                        @foreach($adverts as $advert)

                            <div class="machine-card">
                                <div class="row">
                                    <div>
                                        <a href="{!! route('adverts', $advert->alias) !!}">
                                            <h2 style="    margin: 15px;">{{$advert->name}}</h2>
                                        </a>
                                    </div>

                                    <div class="col-md-6  proposal-wrap ">
                                        <div class="image-wrap">
                                            <a class="thumbnail fancybox" rel="ligthbox"
                                               href="{!! route('adverts', $advert->alias) !!}">
                                                <img alt="{{$advert->name}}"
                                                     src="{{url($advert->photo)}}" class="img-responsive"
                                                     style="max-height: 400px;"></a>
                                            <input id="profile-image-upload" class="hidden" type="file">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="list-params">
                                            <p><strong>Сумма:</strong>{{$advert->sum_format}} руб.
                                            </p>
                                            <p><strong>Категория:</strong>{{$advert->category->name}}
                                            </p>
                                            <p><strong style="margin-bottom: auto">Описание:</strong>{!! nl2br(e($advert->description)) !!}
                                            </p>
                                            <p><strong>Вознаграждение Агентам: </strong> {{$advert->reward->name}} {{$advert->reward_text}}
                                            </p>
                                            <p><strong>Актуально до:</strong> {{$advert->actual_date->format('d.m.Y')}}
                                            </p>

                                            <p><strong>Адрес: </strong>
                                                <span id="addressData">{{$advert->full_address}}</span>
                                            </p>

                                        </div>
                                        <div class="form-item">
                                            <div class="button">
                                                <a class="btn-custom"
                                                   href="{!! route('adverts', $advert->alias) !!}">Просмотр
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
                        {{$adverts->links()}}
                    @endif
                </div>
                @seoBottom
                @contactBottom
            </div>

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
@endsection