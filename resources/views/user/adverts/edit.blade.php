@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <div class="col-md-12 user-profile-wrap box-shadow-wrap">

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="errors" style="display: none">
                            <div class="alert alert-warning" id="alerts" role="alert">


                            </div>
                        </div>
                    </div>
                    <form id="advert_form" class="proposal-wrap" action="{{route('adverts.update', $advert->id)}}">
                        @csrf
                        @method('PATCH')
                        <h3>Изменить объявление</h3>
                        <div class="col-md-6">
                            <helper-select-input :data="{{$categories->toJson()}}"
                                                 :column-name="{{json_encode('Категория объявления')}}"
                                                 :place-holder="{{json_encode('Категория объявления')}}"
                                                 :col-name="{{json_encode('category_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($advert->category->toArray() ?? '')}}"
                                                 :show-column-name="1"></helper-select-input>
                            <div class="form-item">
                                <label class="required">Наименование:</label>
                                <input class="promo_code"
                                       value="{{$advert->name}}"
                                       name="name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Описание:</label>
                                <textarea class="promo_code" style="height: auto;"

                                          name="description">{{$advert->description}}</textarea>
                            </div>

                            <div class="form-item">
                                <label class="required">Сумма сделки:</label>
                                <input class="promo_code"
                                       value="{{$advert->sum / 100}}"
                                       name="sum"
                                       type="number">
                            </div>


                            <helper-select-input :data="{{$rewards->toJson()}}"
                                                 :column-name="{{json_encode('Вознаграждение цепочки агентов')}}"
                                                 :place-holder="{{json_encode('Выберите тип')}}"
                                                 :col-name="{{json_encode('reward_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($advert->reward->toArray() ?? '')}}"
                                                 :show-column-name="1"></helper-select-input>
                            <div class="form-item"
                                 id="reward_text" {!! $advert->reward_id === 1 ? 'style="display: none"' : '' !!} >
                                <label class="required">@if($advert->reward_id === 2)
                                        Какой? @elseif($advert->reward_id === 3) Сколько? @else @endif</label>
                                <input class="promo_code"
                                       placeholder="@if($advert->reward_id === 2) Введите описание @elseif($advert->reward_id === 3) Введите сумму @else @endif"
                                       value="{{$advert->reward_text}}"
                                       name="reward_text"
                                       type="text">
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-item">
                                <label class="required">Актуально до:</label>
                                <input class="promo_code" data-toggle="datepicker"
                                       value="{{$advert->actual_date->format('Y/m/d')}}"
                                       name="actual_date"
                                       type="text">
                            </div>
                            <helper-select-input :data="{{$regions->toJson()}}"
                                                 :column-name="{{json_encode('Выберите регион')}}"
                                                 :place-holder="{{json_encode('Регион')}}"
                                                 :col-name="{{json_encode('region_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($advert->region->toArray() ?? '')}}"
                                                 :show-column-name="1"
                                                 :hide-city="1">
                            </helper-select-input>

                            <helper-select-input :data="{{ json_encode([])}}"
                                                 :column-name="{{json_encode('Город')}}"
                                                 :place-holder="{{json_encode('Город')}}"
                                                 :col-name="{{json_encode('city_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($advert->city->toArray() ?? '')}}"
                                                 :show-column-name="1"
                                                 :hide-city="1">
                            </helper-select-input>

                            <div class="form-item">
                                <label class="required">Адрес:</label>
                                <input class="promo_code"
                                       value="{{$advert->address}}"
                                       name="address"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label for="checked-input" class="checkbox">
                                    Отображать в общем списке
                                    <input type="checkbox" name="global_show" value="1"
                                           id="checked-input" {{$advert->global_show ? 'checked' : ''}}>
                                    <span class="checkmark"></span>
                                </label>

                            </div>
                        </div>

                        <div class="col-xs-5">
                            <h4>Фото</h4>

                            <helper-image-loader multiple-data="0" col-id="photoTech" col-name="photo"
                                                 :required="1"
                                                 :exist="{{json_encode([$advert->photo])}}"
                                                 :url="{{json_encode(route('machinery.load-files'))}}"
                                                 :token="{{json_encode(csrf_token())}}"></helper-image-loader>

                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-12">
                            <div class="button">
                                <button type="submit" id="create_advert" class="btn-custom">
                                    Сохранить изменения
                                </button>
                            </div>
                        </div>


                    </form>

                </div>
            </div>
        </div>
    </div>

    @push('after-scripts')
        <script>
            $(document).on('reward_id', function (e, name, value, id) {
                $('#reward_text').hide()

                switch (id) {
                    case 1:
                        $('#reward_text').hide()
                        break;
                    case 2:

                        $('#reward_text label').html('Какой?')
                        $('#reward_text input').attr('placeholder', 'Введите описание')
                        $('#reward_text').show()
                        break;
                    case 3:
                        $('#reward_text label').html('Сколько?')
                        $('#reward_text input').attr('placeholder', 'Введите сумму')
                        $('#reward_text').show()
                        break;
                }

            })
            $(document).on('submit', '#advert_form', function (e) {
                e.preventDefault()
                let $form = $(this);

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (e) {
                        showMessage(e.message)
                        setTimeout(function () {
                            window.location = e.url
                        }, 3000)

                    },
                    error: function (e) {
                        showErrors(e)
                    }
                })
            })
        </script>
    @endpush
@endsection