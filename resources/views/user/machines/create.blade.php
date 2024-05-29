@extends('layouts.main')
@section('content')
    <div class="create-add-machine">
        <div class="pull-right">
            <a href="/howdoesitwork/contractor/44/11" target="_blank">Инструкция</a>
        </div>
        <div class="title">
            <h1>Добавление техники</h1>
        </div>

        <div class="cols-table">
            <input type="hidden" id="_all_countries"
                   value="{{json_encode($countries = \App\Support\Country::with('machine_masks')->get())}}">
            <input type="hidden" id="_default"
                   value="{{json_encode(Auth::user()->country->toArray())}}">
            <form class="form" action="#" method="post" id="registrationMachine" novalidate autocomplete="disabled">
                @csrf
                <div class="row">

                    <div class="col-md-6">
                        <div class="col-xs-6">
                            <div class="form-item">
                                <label for="radio-input-yes" class="radio">
                                    @lang('transbaza_machine_edit.machinery')
                                    <input type="radio" name="machine_type" value="machine" id="radio-input-yes"
                                           checked>
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-item">
                                <label for="radio-input-no" class="radio">
                                    @lang('transbaza_machine_edit.equipment')
                                    <input type="radio" name="machine_type" value="equipment" id="radio-input-no">
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="col-md-6">
                        <helper-select-input :data="{{$countries}}"
                                             :column-name="{{json_encode('Выберите страну')}}"
                                             :place-holder="{{json_encode('Выберите страну')}}"
                                             :col-name="{{json_encode('country_id')}}"
                                             :required="1"
                                             :initial="{{json_encode(Auth::user()->country)}}"
                                             :show-column-name="1"></helper-select-input>
                    </div>
                   <div class="col-md-6"></div>
                    <div class="col-md-4 ">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.state_number')
                                <input type="text" id="gov-number" class="number" name="number"
                                       style="text-transform:uppercase"
                                       placeholder="A999AA 60" required>
                                <span class="error" style="display: none;">@lang('transbaza_machine_edit.state_number_reserve')
                                    <span class="id-owner"></span>
                                    @lang('transbaza_machine_edit.support_contact')</span>
                                <p>@lang('transbaza_machine_edit.number_example')</p>
                                <span><img style="max-height: 40px" id="mask1" src=""></span>
                                <span><img style="max-width: 60px" id="mask2" src=""></span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4" id="machine_select">
                        <helper-select-input
                                :data="{{\App\Machines\Type::whereType('machine')->orderBy('name')->get()->toJson()}}"
                                :column-name="{{json_encode(trans('transbaza_machine_edit.machinery_category'))}}"
                                :place-holder="{{json_encode(trans('transbaza_machine_edit.machinery_category'))}}"
                                required="1"
                                :show-column-name="1"
                                :col-name="{{json_encode('type')}}"></helper-select-input>
                    </div>
                    <div class="col-md-4" id="equipment_select" style="display: none; margin-right: 1.5%;">
                        <helper-select-input
                                :data="{{\App\Machines\Type::whereType('equipment')->orderBy('name')->get()->toJson()}}"
                                :column-name="{{json_encode(trans('transbaza_machine_edit.equipment_category'))}}"
                                :place-holder="{{json_encode(trans('transbaza_machine_edit.equipment_category'))}}"
                                required="1"
                                :show-column-name="1"
                                :col-name="{{json_encode('type_eq')}}"></helper-select-input>
                    </div>
                    <div class="col-md-4">
                        <helper-select-input :data="{{\App\Machines\Brand::all()->toJson()}}"
                                             :column-name="{{json_encode(trans('transbaza_machine_edit.brand'))}}"
                                             :place-holder="{{json_encode(trans('transbaza_machine_edit.brand'))}}"
                                             required="1"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('brand_id')}}"></helper-select-input>
                    </div>
                </div>
                <div class="show-if-number one-cols-list">
                    <div class="col">
                        <div class="form-item">
                            <label>
                                @lang('transbaza_machine_edit.name')
                                <input type="text" placeholder=" @lang('transbaza_machine_edit.name') " name="name">
                            </label>
                        </div>
                    </div>
                </div>
                <div class="show-if-number row">
                    <div class="col-md-4">
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
                                             :column-name="{{json_encode(trans('transbaza_machine_edit.region'))}}"
                                             :place-holder="{{json_encode(trans('transbaza_machine_edit.region'))}}"
                                             required="1"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('region')}}"
                                             :initial="{{json_encode($initial_region ?? '')}}"
                                             :hide-city="1">
                        </helper-select-input>
                    </div>
                    <div class="col-md-4">
                        <helper-select-input :data="{{$cities_data->toJson()}}"
                                             :column-name="{{json_encode(trans('transbaza_machine_edit.city'))}}"
                                             :place-holder="{{json_encode(trans('transbaza_machine_edit.city'))}}"
                                             required="1"
                                             :hide-city="1"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('city_id')}}"
                                             :initial="{{json_encode($checked_city_source ?? '')}}">
                        </helper-select-input>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label>
                                @lang('transbaza_machine_edit.base_address')
                                <input type="text" name="address" id="address"
                                       autocomplete="off"
                                       value="{{$region->name}}, {{$checked_city->name ?? ''}}"
                                       placeholder="  @lang('transbaza_machine_edit.base_address') " required>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="show-if-number row">
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.cost_per_hour')
                                <input type="text" name="sum_hour" id="sum_hour"
                                       placeholder="*    @lang('transbaza_machine_edit.cost_per_hour') " required>

                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.work_day_duration')
                                <input type="number" step="1" id="change_hour" name="change_hour"
                                       value="8"
                                       placeholder="*  @lang('transbaza_machine_edit.work_day_duration') ">
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.cost_per_day')
                                <input type="text" name="sum_day" id="sum_day"
                                       placeholder="*   @lang('transbaza_machine_edit.cost_per_day') " required>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="show-if-number one-cols-list">
                    <div class="col roller-item">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4> @lang('transbaza_machine_edit.photo') </h4>
                        </div>
                        <div class="content">
                            <div class="col-md-12">
                                <helper-image-loader multiple-data="1" col-id="photoTech" col-name="photo[]"
                                                     :required="1"
                                                     :url="{{json_encode(route('machinery.load-files'))}}"
                                                     :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="equipments_fields">
                </div>
                <div id="optional_fields">

                </div>

                <div class="show-if-number">
                    <div class="col roller-item">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4>@lang('transbaza_machine_edit.additionally')</h4>
                        </div>
                        <div class="content">
                            <div class="show-if-number row">
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label for="type-account">
                                            <div class="custom-select-exp">
                                                <p>@lang('transbaza_machine_edit.regional_representative')</p>
                                                <select name="regional_representative_id">
                                                    <option value="" selected>Региональный представитель</option>
                                                    @foreach(\App\User::where('is_regional_representative', 1)->where('id', '!=', Auth::user()->id)->get() as $user)
                                                        <option value="{{$user->id}}" {{Auth::user()->regional_representative_id === $user->id ? 'selected' : ''}}>
                                                            РП #{{$user->id}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label for="type-account">
                                            <div class="custom-select-exp">
                                                <p>@lang('transbaza_machine_edit.promoter')</p>
                                                <select name="promoter_id">
                                                    <option value="" selected>Промоутер</option>
                                                    @foreach(\App\User::where('is_promoter', 1)->where('id', '!=', Auth::user()->id)->get() as $user)
                                                        <option value="{{$user->id}}">ПР #{{$user->id}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="show-if-number row">
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label>
                                            @lang('transbaza_machine_edit.sticker_promo_code')
                                            <input type="text" name="sticker_promo_code"
                                                   placeholder="Промо-код наклейки">
                                        </label>
                                    </div>
                                </div>
                                <div class="col roller-item">
                                    <div class="item">
                                        <i class="fas fa-plus active"></i>
                                        <i class="fas fa-minus"></i>
                                        <h4>@lang('transbaza_machine_edit.photo_with_sticker')</h4>
                                    </div>
                                    <div class="content">
                                        <helper-image-loader multiple-data="0" col-id="photoSticker" col-name="sticker"
                                                             :url="{{json_encode(route('machinery.load-files'))}}"
                                                             :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                                    </div>
                                </div>
                            </div>
                            <div class="show-if-number row">
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label>
                                            @lang('transbaza_machine_edit.certificate_number')
                                            <input type="text" name="act_number"
                                                   placeholder="@lang('transbaza_machine_edit.certificate_number')">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label>
                                            @lang('transbaza_machine_edit.psm_number')
                                            <input type="text" name="psm_number"
                                                   placeholder="  @lang('transbaza_machine_edit.psm_number')">
                                            <span class="error" style="display: none;">Транспортное средство с данном номером
                                уже зарегистрировано в системе.
                                Введите, пожалуйста, другой номер или обратитесь в службу поддержки</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 roller-item">
                                    <div class="item">
                                        <i class="fas fa-plus active"></i>
                                        <i class="fas fa-minus"></i>
                                        <h4>@lang('transbaza_machine_edit.documents_scan')</h4>
                                    </div>
                                    <div class="content">
                                        <helper-image-loader multiple-data="1" col-id="scsnsTech" col-name="scans[]"
                                                             :url="{{json_encode(route('machinery.load-files'))}}"
                                                             :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                                    </div>
                                </div>
                            </div>
                            <div class="show-if-number one-cols-list machine_fields_">
                                <div class="col roller-item">
                                    <div class="item">
                                        <i class="fas fa-plus active"></i>
                                        <i class="fas fa-minus"></i>
                                        <h4>@lang('transbaza_machine_edit.registration_certificate')</h4>
                                    </div>
                                    <div class="content">
                                        <div class="two-part">
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.issue_year')
                                                    <input type="text" name="year_release" placeholder="Год выпуска">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.owner')
                                                    <input type="text" name="owner" placeholder="Владелец">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    Выдано на основании
                                                    <input type="text" name="basis_for_witness"
                                                           placeholder="Свидетельство выдано на основании">
                                                </label>
                                            </div>
                                            <div class="form-item image-item end">
                                                <label>
                                                    @lang('transbaza_machine_edit.certificate_date')
                                                    <input type="text" data-toggle="datepicker" name="witness_date"
                                                           placeholder="Дата свидетельства">
                                                    <span class="image date"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="show-if-number one-cols-list machine_fields_">
                                <div class="col roller-item">
                                    <div class="item">
                                        <i class="fas fa-plus active"></i>
                                        <i class="fas fa-minus"></i>
                                        <h4>@lang('transbaza_machine_edit.details_psm')</h4>
                                    </div>
                                    <div class="content ">
                                        <div class="two-part">
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.serial_number')
                                                    <input type="text" name="psm_manufacturer_number"
                                                           placeholder="Заводской номер">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.engine')
                                                    <input type="text" name="engine"
                                                           placeholder=" @lang('transbaza_machine_edit.engine')">
                                                </label>
                                            </div>

                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.transmission')
                                                    <input type="text" name="transmission"
                                                           placeholder=" @lang('transbaza_machine_edit.transmission')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.leading_bridge')
                                                    <input type="text" name="leading_bridge"
                                                           placeholder=" @lang('transbaza_machine_edit.leading_bridge')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.color')
                                                    <input type="text" name="colour"
                                                           placeholder=" @lang('transbaza_machine_edit.color')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.engine_type')
                                                    <input type="text" name="engine_type"
                                                           placeholder=" @lang('transbaza_machine_edit.engine_type')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.engine_power')
                                                    <input type="text" name="engine_power"
                                                           placeholder="@lang('transbaza_machine_edit.engine_power')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.construction_weight')
                                                    <input type="text" name="construction_weight"
                                                           placeholder="   @lang('transbaza_machine_edit.construction_weight')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.max_construction_speed')
                                                    <input type="text" name="construction_speed"
                                                           placeholder="  @lang('transbaza_machine_edit.max_construction_speed')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.dimensions')
                                                    <input type="text" name="dimensions"
                                                           placeholder="  @lang('transbaza_machine_edit.dimensions')">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hr-line"></div>
                        </div>
                    </div>
                </div>

                <div class="show-if-number">
                    <div class="button two-btn">
                        <button class="btn-custom" type="submit">
                            Добавить
                        </button>
                        <a href="/contractor/machinery" class="btn-custom">Отмена</a>
                        <button class="btn-custom" id="reset-btn" type="reset" style="display: none;"> Отмена</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
@push('after-scripts')
    <script>
        var all_countries = JSON.parse($('#_all_countries').val())
        var _default = JSON.parse($('#_default').val())
        var __machine_page = 1;
        $(document).on('type type_eq', function (e, name, value, id) {
            console.log(value);
            $.ajax({
                url: '{!! route('machine_option_fields') !!}',
                type: 'GET',
                data: {type_id: id},
                success: function (e) {
                    $('#optional_fields').html(e.options)
                    /*  $('#equipments_fields').html(e.equipments)*/
                },
                error: function () {
                    $('#optional_fields').html('')
                    $('#equipments_fields').html('')
                }

            })

        })
        $(document).ready(function () {
            // $('.show-if-number').hide()
            /*27хн7843*/
            let masks = [
                _default['machine_masks'][0]['mask'],
                _default['machine_masks'][1]['mask']
            ]
            $('#mask1').attr('src', '/' + _default['machine_masks'][0]['image'] )
            $('#mask2').attr('src', '/' + _default['machine_masks'][1]['image'] )
            $(document).on('country_id', function (e, name, value, id) {
                 masks = [];
                for (key in all_countries){
                    let country = all_countries[key]
                    if(country['id'] === id){
                        $('.number').val('')
                        masks.push(country['machine_masks'][0]['mask']);
                        masks.push(country['machine_masks'][1]['mask']);
                        $('#mask1').attr('src', '/' + country['machine_masks'][0]['image'] )
                        $('#mask2').attr('src', '/' + country['machine_masks'][1]['image'] )
                    }
                }
            })


            let options = {
                translation: {
                    A: {
                        pattern: /[A-Za-z]/
                    },
                    Z: {
                        pattern: /[А-Яа-я]/
                    },
                    Y: {
                        pattern: /[0-9]/, optional: true
                    },
                    I: {
                        pattern: /[А-Яа-я0-9]/
                    },
                    placeholder: 'A999AA 60',
                },
                onComplete: function (cep) {
                    checkNumber(cep);
                },
                onKeyPress: function (cep, event, currentField, options) {
                    if (cep.match(/^[А-Яа-я](.*)?$/igm)) {
                        $('.number').mask(masks[0], options)
                    } else {
                        $('.number').mask(masks[1], options)
                    }
                    if (cep.length >= 9) {
                        checkNumber(cep)
                    }
                },
            };
            $('.number').mask('IIIIIIIIIII', options)

            $('#registrationMachine').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '/contractor/machinery',
                    type: 'POST',
                    data: new FormData($('#registrationMachine')[0]),
                    processData: false,
                    contentType: false,
                    async: false,
                    success: function (data) {
                        showMessage(data.message);
                        $('#reset-btn').trigger('click');
                        setTimeout(function () {
                            location.href = '/contractor/machinery'
                        }, 2000)
                    },
                    error: function (message) {
                        //   showModalErrors(message)
                        showMessage('Обнаружены ошибки. Проверьте правильность заполнения полей.');
                        showErrors(message);

                    }
                })
            })


        })
        $(document).on('change', '[name=machine_type]', function () {
            if ($(this).val() === 'equipment') {
                $('.number').closest('.col').hide();
                /*machine_select
                equipment_select*/
                $('#machine_select').hide();
                $('#equipment_select').show();
                $('.machine_fields_').hide();
                $('.show-if-number').css('display', 'block')
            } else {
                $('#machine_select').show();
                $('.machine_fields_').show();
                $('#equipment_select').hide();
                $('.number').closest('.col').show()
                $('.number').trigger($.Event('keypress', {keycode: 13}))
            }
        });
        $(document).on('click', '.roller-item .item', function () {
            $(this).siblings('.content').toggleClass('active')
            $(this).find('.fas').toggleClass('active')
        })
        $(document).on('input', '#sum_hour, #change_hour', function () {
            var hour = $('#sum_hour').val();
            var count = $('#change_hour').val();
            if (hour && count) {
                $('#sum_day').val(hour * count)
            }
        })

        function checkNumber(number) {
            // check-number
            var data = {
                number: number.toUpperCase(),
                _token: '{{ csrf_token() }}'
            }
            $.ajax({
                type: 'POST',
                url: '/check-number-machinery',
                data: data,
                success: function () {
                    $('.number').parent().find('.error').hide()
                    $('.show-if-number').css('display', 'block')
                },
                error: function (err) {
//                    console.log(err.responseJSON.owner_id)
                    $('.show-if-number').hide()
                    if (err.status == 400) {
                        var errorWrap = $('.number').parent().find('.error');
                        errorWrap.show()
                        errorWrap.find('.id-owner').empty().append('#' + err.responseJSON.owner_id)
                    }
                }
            })
        }
    </script>
@endpush
