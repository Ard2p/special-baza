@extends('layouts.main')
@section('header')
    <meta name="description" content="Техника владельца #{{$user->id}} в наличие для аренды">
    <meta name="keywords" content="техника спецтехника заказ аренда онлайн цены">
    <title>TRANSBAZA - Владелец техники #{{$user->id}}, техника в наличие для аренды</title>
@endsection
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <form action="" class="machine-card" method="GET">
                <h1 class="text-center">#{{$user->id ?? ''}} БЫСТРЫЙ ЗАКАЗ СПЕЦТЕХНИКИ</h1>
                <div class="search-wrap machineries-wrap row" style="padding: 0">

                    <div class="col-md-12 harmony-accord">
                        <h3>Параметры списка</h3>
                        <div class="list-params">
                            <div class="machinery-filter-wrap">
                                <div class="tree-cols-list">
                                    <div class="col">
                                        <helper-select-input :data="{{$types->toJson()}}"
                                                             :column-name="{{json_encode('Категория техники')}}"
                                                             :place-holder="{{json_encode('Выберите категорию')}}"
                                                             :col-name="{{json_encode('type_id')}}"
                                                             :depend="1"
                                                             :types-url="{{json_encode(['url' => 'api/get-user-regions'])}}"
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
                                                             :cities="{{json_encode(['url' => 'api/get-user-cities'])}}"

                                                             :types-region-url="{{json_encode(['url' => 'api/get-user-types'])}}"
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
        <div id="machine_show" class=" article-wrap">
            @seoTop
            @contactTop
            <div class="col-md-9 col-md-push-3">
                @if(!$user)
                    <div class="text-center">
                        {{$machines->links( "pagination::bootstrap-4")}}
                    </div>
                @endif

                    <div class="machine-card">
                        @push('styles')
                            <style>
                                .proposal-wrap {
                                    border: 1px solid #cecece;
                                }

                                @media (min-width: 520px) {
                                    .proposal-wrap p {
                                        white-space: nowrap;
                                        overflow: hidden;
                                        text-overflow: ellipsis;
                                        max-width: 75ch;
                                    }
                                }
                            </style>
                        @endpush
                        @foreach($machines as $machine)


                            <div class="row proposal-wrap" >
                                <div itemscope itemtype="http://schema.org/Product">
                                    @include('user.machines.schema')
                                </div>
                                @include('user.machines.preview_card')
                            </div>

                        @endforeach
                    </div>

                    <div class="clearfix"></div>
                    <div class="margin-wrap"></div>

                @if(!$user)
                    {{$machines->links()}}
                @endif
            </div>
            <div class="col-md-3 col-md-pull-9">
                @include('includes.auth_form')
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