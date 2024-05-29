@extends('layouts.main')
{{--@section('header')
    <meta name="description"
          content="{{($machine->_type->name)}}, {{($machine->brand->name ?? '')}}, {{($machine->region->name ?? '')}}, {{($machine->city->name ?? '')}}, характеристики, фото, предложения по аренде">
    <meta name="keywords"
          content="TRANSBAZA {{mb_strtolower($machine->_type->name)}} {{mb_strtolower($machine->brand->name ?? '')}} {{mb_strtolower($machine->region->name ?? '')}} {{mb_strtolower($machine->city->name ?? '')}} характеристики фото аренда">
    <title>TRANSBAZA – взять в аренду {{mb_strtolower($machine->_type->name)}} {{($machine->brand->name ?? '')}}
        , {{($machine->region->name ?? '')}}, {{($machine->city->name ?? '')}}
    </title>
@endsection--}}
@section('content')

    <div class="container article-wrap bootstrap snippet">
        <ol class="breadcrumb">
            <li><a href="{{route('contractor_service_directory_main')}}">Услуги</a></li>
            <li>
                <a href="{{route('contractor_service_directory_main_category', $service->category->alias)}}">{{$service->category->name_style}}</a>
            </li>
            <li>
                <a href="{{route('contractor_service_directory_main_result', [$service->category->alias, $service->city->alias, $service->region->alias])}}">В
                    городе {{$service->city->name}}, {{$service->region->name}}</a></li>
            <li class="active">{{$service->name}}</li>
        </ol>
        <div class="row">

            <h1 class="text-center">{{$service->user_id}}.{{$service->id}} ФОРМА БЫСТРОГО ЗАКАЗА</h1>
        </div>
        <div class="machine-card" style="padding: 0px">
            <div class="row">
                <div class="col-md-6">
                    <div class="image-wrap">
                        <a class="thumbnail fancybox" rel="ligthbox"
                           href=" /{{$service->photo}}">
                            <img alt="{{$service->category->name}} {{$service->city->name ?? ''}}, {{$service->region->name ?? ''}}"
                                 src="/{{$service->photo}}" class="img-responsive"></a>
                        <input id="profile-image-upload" class="hidden" type="file">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="list-params">
                        <p style="padding: 1px;">
                                                    <span><b>Минимальный объём заказа:</b> {{$service->size}} </span>

                        </p>
                        <p style="padding: 1px;">
                                                    <span><b>Стоимость минимального заказа:</b> {{$service->sum_format}} руб.</span>

                            {{--Нет данных--}}
                        </p>
                        <p>{{$service->text}}</p>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-offset-2  col-md-8">
            <form action="{!! $service->rent_url !!}" id="big_service_form"
                  style="padding: 10px;background: {{$service->settings['color']}};
                          border-color: {{$service->settings['border']}};
                          ">
                @csrf
                <div class="form-item small">
                    <label class="required">
                        Кто заказывает?
                    </label>
                </div>
                <div class="form-item small">
                    <label>
                        <input type="text" name="_email" placeholder="Email" value="{{Auth::check() ? Auth::user()->email : ''}}" {{Auth::check() ? 'disabled' : ''}}>
                    </label>
                </div>
                <div class="form-item small">
                    <label>
                        <input type="text" name="_phone" class="phone" value="{{Auth::check() ? Auth::user()->phone : ''}}"
                               placeholder="Номер телефона" {{Auth::check() ? 'disabled' : ''}}>
                    </label>
                </div>
                <helper-select-input :data="{{\App\Support\Region::all()->toJson()}}"
                                     :column-name="{{json_encode('Адрес выполнения работ')}}"
                                     :place-holder="{{json_encode('Выберите регион')}}"
                                     :col-name="{{json_encode('region_id')}}"
                                     :required="1"
                                     :initial="{{json_encode($service->region ?: '')}}"
                                     :initial-city="{{json_encode($service->city ?: '')}}"
                                     :show-column-name="1"
                                     :hide-city="1">
                </helper-select-input>

                <helper-select-input :data="{{json_encode([])}}"
                                     :column-name="{{json_encode('')}}"
                                     :place-holder="{{json_encode('Город')}}"
                                     :required="0"
                                     :col-name="{{json_encode('city_id')}}"
                                     :initial="{{json_encode($service->city ?: '')}}"
                                     :show-column-name="1"
                                     :hide-city="1"></helper-select-input>
                <div class="form-item small">
                    <label for="price">
                        <input type="text" name="address" placeholder="Местонахождение">
                    </label>
                </div>
                <div class="clearfix"></div>
                <label class="required">Дата и время начала работы:</label>
                <div class="clearfix"></div>

                <div class="detail-search">
                    <div class="machinery-filter-wrap">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-item">
                                    <label style="white-space: nowrap">День
                                        <input data-toggle="datepicker" name="date" autocomplete="off" style="    width: 80%;">
                                    </label>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-item" style="white-space: nowrap; margin-right: 31px;">
                                    <label style="width: 27px;">Час

                                    </label>
                                    <input data-toggle="_timepicker" name="time" autocomplete="off" value="08:00" style="width: 74px">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-item">
                    <label for="">
                        <textarea rows="3" name="comment" placeholder="Укажите обьём работ  и потребность в технике (какой и сколько единиц)" style="height: auto;"></textarea>
                    </label>
                </div>
                <input type="hidden" name="alias" value="{{$service->alias}}">
                <input type="hidden" name="contractor_service_id" value="{{$service->id}}">
                <input type="hidden" name="url" value="{!! request()->fullUrl() !!}">
                <div class="form-item">
                    <div class="button">
                        <button type="submit" class="btn-custom submit_simple_btn"
                                style="background: {{$service->settings['button_color']}};color: {{$service->settings['button_text_color']}};">
                            Заказать
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    {!! \App\Marketing\ShareList::renderShare() !!}
    @push('after-scripts')
        <script>
            $('#tabs-panel a').click(function () {
                $('#tabs-panel a').removeClass('black')
                $(this).addClass('black')
            })
            $(document).on('submit', 'form', function (e) {
                e.preventDefault();
                var $form = $(this);
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (e) {
                        $form[0].reset()
                        showMessage(e.message)
                    },
                    error: function (e) {
                        showErrors(e)
                        e = e.responseJSON;
                        if (e.email !== undefined || e.phone !== undefined || e.name !== undefined) {
                            $(window).scrollTop($('#machine_show').offset().top);
                        }
                    }
                })

            })
            $('[data-toggle="_datepicker"]').datetimepicker({
                format: 'Y/m/d',
                dayOfWeekStart: 1,
                timepicker: false
            });
            $('[data-toggle="_timepicker"]').datetimepicker({
                format: 'H:i',
                dayOfWeekStart: 1,
                datepicker: false
            });
            $('.__radio').change(function () {
                var modal = $(this).closest('#machine_show')
                if (this.value == 'old') {
                    modal.find('input[name=name], input[name=phone]').hide();
                    modal.find('input[name=email]').attr("placeholder", "Мой логин (email)");
                }
                else if (this.value == 'new') {
                    modal.find('input[name=name], input[name=phone]').show();
                    modal.find('input[name=email]').attr("placeholder", "Email");
                }
            });
        </script>
    @endpush
    @include('scripts.machine.show')
@endsection