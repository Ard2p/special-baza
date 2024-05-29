@extends('layouts.main')
@section('content')
    <div class="create-add-machine">
        <div class="title">
            <h1>Добавление услуги</h1>
        </div>
        <div class="cols-table">
            <form class="form" action="{{route('my-services.update', $service->id)}}" method="post" id="updateService"  novalidate autocomplete="disabled">
                @csrf
                @method('PATCH')
                <div class="three-cols-list">

                    <div class="col">
                        <helper-select-input :data="{{\App\Directories\ServiceCategory::all()->toJson()}}"
                                             :column-name="{{json_encode('Категория Услуги')}}"
                                             :place-holder="{{json_encode('Категория Услуги')}}"
                                             required="1"
                                             :show-column-name="1"
                                             :initial="{{json_encode($service->service ?? '')}}"
                                             :col-name="{{json_encode('service_category_id')}}"></helper-select-input>
                    </div>
                    <div class="col">
                        @php
                            $region = \App\Support\Region::find(Auth::user()->native_region_id);
                           if ($region) {
                               $initial_region = ['id' => $region->id, 'name' => $region->full_name];
                               $cities_data = $region->cities;
                               $checked_city = \App\City::find(Auth::user()->native_city_id);

                               if ($checked_city) {
                                    $checked_city_source = ['id' => $checked_city->id, 'name' => $checked_city->with_codes];
                               }
                           }else{
                             $cities_data = [];
                             }
                        @endphp
                        <helper-select-input :data="{{\App\Support\Region::all()->toJson()}}"
                                             :column-name="{{json_encode('Регион')}}"
                                             :place-holder="{{json_encode('Регион')}}"
                                             required="1"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('region_id')}}"
                                             :initial="{{json_encode($service->region)}}"
                                             :hide-city="1">
                        </helper-select-input>
                    </div>
                    <div class="col">
                        <helper-select-input :data="{{$cities_data->toJson()}}"
                                             :column-name="{{json_encode('Город')}}"
                                             :place-holder="{{json_encode('Город')}}"
                                             required="1"
                                             :hide-city="1"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('city_id')}}"
                                             :initial="{{json_encode($service->city)}}">
                        </helper-select-input>
                    </div>
                </div>
                <div class="one-cols-list">
                    <div class="col">
                        <div class="form-item">
                            <label>
                                Наименование
                                <input type="text" placeholder="Наименование" name="name" value="{{$service->name}}">
                            </label>
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-item">
                            <label>
                                Минимальный объём заказа
                                <input type="text" name="size" value="{{$service->size}}"
                                       placeholder="">
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label>
                                Стоимость минимального заказа
                                <input type="number" name="sum" value="{{$service->sum_format}}"
                                       placeholder="">
                            </label>
                        </div>
                    </div>
                </div>
                <div id="#optional_fields">

                    <div class="col roller-item">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4>Дополнительно</h4>
                        </div>
                        <div class="content ">
                            <div class="col-md-12">
                                @foreach($options as $option)
                                    <div class="col-md-6">
                                        <div class="form-item">
                                            <label>
                                                {{$option->name}}  ({{$option->unit->name ?? ''}})
                                                <input type="text"
                                                       {{$option->field === 'date' ?'data-toggle="datepicker"' : ''}}
                                                       name="option_cat{{$service->service_category_id}}_{{$option->id}}"
                                                       value="{{$service->optionalAttributes->contains($option)
                                           ? $service->optionalAttributes->where('id', $option->id)->first()->pivot->value : ''}}"
                                                       placeholder="">
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
                <div class="one-cols-list">
                    <div class="col">
                        <div class="form-item">
                            <label>
                                Описание услуги
                                <textarea type="text" placeholder="" rows="5" cols="10" name="text" style="height: auto">{{$service->text}}</textarea>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="one-cols-list">
                    <div class="col roller-item">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4>Фото Услуги</h4>
                        </div>
                        <div class="content">
                            <div class="col-md-12">
                                <helper-image-loader multiple-data="0" col-id="photoTech" col-name="photo"
                                                     :required="1"
                                                     :exist="{{json_encode([$service->photo])}}"
                                                     :url="{{json_encode(route('machinery.load-files'))}}"
                                                     :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-6">
                        <div class="button">

                            <button class="btn-custom" type="submit">
                                Сохранить
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="button">

                            <a href="{{route('my-services.index')}}" class="btn-custom">Отмена</a>
                        </div>
                        <button class="btn-custom" id="reset-btn" type="reset" style="display: none;"> Отмена</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
@push('after-scripts')
    <script>
        $(document).ready(function () {
            // $('.show-if-number').hide()

            $('#updateService').on('submit', function (e) {
                let $form = $(this);
                e.preventDefault();
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: new FormData($('#updateService')[0]),
                    processData: false,
                    contentType: false,
                    async: false,
                    success: function (data) {
                        showMessage(data.message);
                    },
                    error: function (message) {
                        //   showModalErrors(message)
                        showMessage('Обнаружены ошибки. Проверьте правильность заполнения полей.');
                        showErrors(message);

                    }
                })
            })
            $(document).on('click', '.roller-item .item', function () {
                $(this).siblings('.content').toggleClass('active')
                $(this).find('.fas').toggleClass('active')
            })
            $(document).on('service_category_id', function (e, name, value, id) {
                $.ajax({
                    url: '{!! route('my-services.index', ['get_options' => 1]) !!}',
                    type: 'GET',
                    data: {type_id: id},
                    success: function (e) {
                        $('#optional_fields').html(e.data)
                    },
                    error: function () {
                        $('#optional_fields').html('')
                    }

                })

            })

        })
    </script>
@endpush
