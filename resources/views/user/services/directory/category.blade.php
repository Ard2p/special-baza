@extends('layouts.main')
@section('header')
    <meta name="description" content="{{($category->name)}}, доступная для заказа спецтехника">
    <meta name="keywords" content="TRANSBAZA {{mb_strtolower($category->name)}} цена заказать">
    <title>TRANSBAZA – аренда {{mb_strtolower($category->name_style)}}</title>
@endsection
@section('content')
    <div class="container article-wrap">


        <div class="col-md-9 col-sm-12 col-md-push-3">
            <ol class="breadcrumb">
                <li><a href="{{route('directory_main')}}">Услуги</a></li>
                <li class="active">{{$category->name_style}}</li>
            </ol>
            <h1 style="background: yellow">{{$category->name_style}}</h1>
            <form id="spec-category" action="{{route('contractor_service_directory_main_category', $category->alias)}}">
                <div class="machinery-filter-wrap">

                    <div class="tree-cols-list">
                        <div class="col">
                            <div style="display: none">
                            <helper-select-input :data="{{\App\Directories\ServiceCategory::whereId($category->id)->get()->toJson()}}"
                                                 :column-name="{{json_encode('Категория техники')}}"
                                                 :place-holder="{{json_encode('Категория техники')}}"
                                                 :col-name="{{json_encode('type')}}"
                                                 :show-column-name="1"
                                                 :initial="{{json_encode($category ?? '')}}"></helper-select-input>
                            </div>
                            <helper-select-input :data="{{$regions->toJson()}}"
                                                 :column-name="{{json_encode('Регион')}}"
                                                 :place-holder="{{json_encode('Выберите регион')}}"
                                                 :col-name="{{json_encode('region')}}"
                                                 :required="0"

                                                 :user-id="{{$user->id ?? 0}}"
                                                {{-- :cities="{{json_encode(['url' => 'api/get-user-cities'])}}"--}}
                                                 :cities="{{json_encode(['url' => "api/get-tb-feel-cities/{$category->id}"])}}"

                                                 :types-region-url="{{json_encode(['url' => 'api/get-user-types'])}}"
                                                 :depend="1"
                                                 :initType="{{$category->id}}"
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
                                                 :initType="{{$category->id}}"
                                                 :depend="1"
                                                 :user-id="{{$user->id ?? 0}}"
                                                 :col-name="{{json_encode('city_id')}}"
                                                 :initial="{{json_encode($checked_city_source ?? '')}}"
                                                 :show-column-name="1"
                                                 :hide-city="1"></helper-select-input>
                        </div>
                        <div class="col">
                            <div class="button">
                                <button type="submit" class="btn-custom">Показать</button>
                            </div>
                        </div>

                    </div>


                </div>
            </form>
            <div id="__reions_table">

                @include('user.services.directory.table')

            </div>
            @seoBottom
            @contactBottom
            {!! \App\Marketing\ShareList::renderShare() !!}
        </div>

        <div class="col-md-3    col-md-pull-9">
            @include('includes.auth_form')
        </div>

    </div>
    @push('after-scripts')
        <script>
            $(document).on('submit', '#spec-category', function (e) {
                e.preventDefault();
                var form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    type: 'GET',
                    data: form.serialize(),
                    success: function (response) {
                        $('#__reions_table').html(response.table)
                    },
                    error: function (r) {
                        showErrors(r);
                    }
                })
            })
        </script>
    @endpush
@endsection