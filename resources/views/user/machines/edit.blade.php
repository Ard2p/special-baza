@extends('layouts.main')
@section('content')
    <div class="create-add-machine">
        <div class="title">
            <h1>@lang('transbaza_machine_edit.edit_page_title')</h1>
        </div>
        <div class="cols-table">
            <form class="form" action="#" method="post" id="machineEdit" autocomplete="disable">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="col-xs-6">
                            <div class="form-item">
                                <label for="radio-input-yes" class="radio">
                                    @lang('transbaza_machine_edit.machinery')
                                    <input type="radio" name="machine_type" value="machine"
                                           id="radio-input-yes" {{$machine->machine_type ==='machine' ? 'checked' : ''}}>
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-item">
                                <label for="radio-input-no" class="radio">
                                    @lang('transbaza_machine_edit.equipment')
                                    <input type="radio" name="machine_type" value="equipment"
                                           id="radio-input-no" {{$machine->machine_type ==='equipment' ? 'checked' : ''}}>
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <input type="hidden" id="_all_countries"
                           value="{{json_encode($countries = \App\Support\Country::with('machine_masks')->get())}}">
                    <input type="hidden" id="_default"
                           value="{{json_encode($machine->region->country)}}">
                    <div class="col-md-6">
                        <helper-select-input :data="{{$countries}}"
                                             :column-name="{{json_encode('Выберите страну')}}"
                                             :place-holder="{{json_encode('Выберите страну')}}"
                                             :col-name="{{json_encode('country_id')}}"
                                             :required="1"
                                             :initial="{{json_encode($machine->region->country)}}"
                                             :show-column-name="1"></helper-select-input>
                    </div>
                </div>
                <div class="three-cols-list">
                    <div class="col col-small" style="display: {{$machine->machine_type === 'machine' ? '' : 'none'}};">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.state_number')
                                <input type="text" id="gov-number" class="number" name="number"
                                       style="text-transform:uppercase"
                                       value="{{str_replace(' ', '', $machine->number)}}"
                                       placeholder="A999AA 60" required>
                                <span class="error" style="display: none;">@lang('transbaza_machine_edit.state_number_reserve')
                                    <span class="id-owner"></span>
                                    @lang('transbaza_machine_edit.support_contact')</span>
                            </label>
                        </div>
                    </div>
                    <div class="col" id="machine_select"
                         style="display: {{$machine->machine_type === 'machine' ? '' : 'none'}};">
                        <helper-select-input :data="{{\App\Machines\Type::whereType('machine')->orderBy('name')->get()->toJson()}}"
                                             :column-name="{{json_encode(trans('transbaza_machine_edit.machinery_category'))}}"
                                             :place-holder="{{json_encode(trans('transbaza_machine_edit.machinery_category'))}}"
                                             required="1"
                                             :initial="{{json_encode($machine->machine_type ==='equipment' ? '' :$machine->_type)}}"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('type')}}"></helper-select-input>
                    </div>
                    <div class="col" id="equipment_select"
                         style="display: {{$machine->machine_type === 'equipment' ? '' : 'none'}}; margin-right: 1.5%;">
                        <helper-select-input :data="{{\App\Machines\Type::whereType('equipment')->get()->toJson()}}"
                                             :column-name="{{json_encode(trans('transbaza_machine_edit.equipment_category'))}}"
                                             :place-holder="{{json_encode(trans('transbaza_machine_edit.equipment_category'))}}"
                                             required="1"
                                             :initial="{{json_encode($machine->machine_type ==='equipment' ? $machine->_type : '')}}"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('type_eq')}}"></helper-select-input>
                    </div>
                    <div class="col">
                        <helper-select-input :data="{{\App\Machines\Brand::all()->toJson()}}"
                                             :column-name="{{json_encode(trans('transbaza_machine_edit.brand'))}}"
                                             :place-holder="{{json_encode(trans('transbaza_machine_edit.brand'))}}"
                                             required="1"
                                             :initial="{{json_encode($machine->brand)}}"
                                             :show-column-name="1"
                                             :col-name="{{json_encode('brand_id')}}"></helper-select-input>
                    </div>
                </div>
                <div class="one-cols-list">
                    <div class="col">
                        <div class="form-item">
                            <label>
                                @lang('transbaza_machine_edit.name')
                                <input type="text" name="name" value="{{$machine->edit_name}}"
                                       placeholder=" @lang('transbaza_machine_edit.name') ">
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
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
                                             :initial="{{json_encode($machine->region)}}"
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
                                             :initial="{{json_encode($machine->city ?? '')}}">
                        </helper-select-input>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label>
                                @lang('transbaza_machine_edit.base_address')
                                <input type="text" name="address"
                                       autocomplete="off"
                                       value="{{$machine->edit_address}}"
                                       placeholder="{{$machine->address}}">
                            </label>
                        </div>
                    </div>
                </div>
                <div class="three-cols-list">
                    <div class="col">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.cost_per_hour')
                                <input type="text" name="sum_hour" id="sum_hour" value="{{$machine->sum_hour / 100}}"
                                       placeholder="* @lang('transbaza_machine_edit.cost_per_hour')" required>

                            </label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.work_day_duration')
                                <input type="number" step="1" name="change_hour" id="change_hour"
                                       value="{{$machine->change_hour}}"
                                       placeholder="* @lang('transbaza_machine_edit.work_day_duration') ">
                            </label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-item">
                            <label class="required">
                                @lang('transbaza_machine_edit.cost_per_day')
                                <input type="text" name="sum_day" id="sum_day" value="{{$machine->sum_day / 100}}"
                                       placeholder="*  @lang('transbaza_machine_edit.cost_per_day')" required>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="one-cols-list">
                    <div class="col roller-item">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4>@lang('transbaza_machine_edit.photo')</h4>
                        </div>
                        <div class="content">
                            <helper-image-loader multiple-data="1" col-id="photoTech" col-name="photo[]"
                                                 :required="1"
                                                 :exist="{{($machine->photo)}}"
                                                 :url="{{json_encode(route('machinery.load-files'))}}"
                                                 :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                        </div>
                    </div>
                </div>

                <div id="#optional_fields">

                    <div class="col roller-item" style="width: 100%;">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4>@lang('transbaza_machine_edit.characteristic')</h4>
                        </div>
                        <div class="content ">
                            <div class="col-md-12">
                                @foreach($options as $option)
                                    <div class="col-md-4">
                                        <div class="form-item">
                                            <label>
                                                {{$option->current_locale_name}} ({{$option->unit}})
                                                <input type="text"
                                                       {{$option->field === 'date' ?'data-toggle="datepicker"' : ''}}
                                                       name="option_cat{{$machine->type}}_{{$option->id}}"
                                                       value="{{$machine->optional_attributes->contains($option)
                                           ? $machine->optional_attributes->where('id', $option->id)->first()->pivot->value : ''}}"
                                                       placeholder="">
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
                <div>
                    <div class="col roller-item">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4>@lang('transbaza_machine_edit.additionally')</h4>
                        </div>
                        <div class="content">
                            <div class="col-md-12">
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label for="type-account">
                                            <p>@lang('transbaza_machine_edit.regional_representative')</p>
                                            <div class="custom-select-exp">
                                                <select name="regional_representative_id">
                                                    <option value="" selected>Региональный представитель</option>
                                                    @foreach(\App\User::where('is_regional_representative', 1)->where('id', '!=', Auth::user()->id)->get() as $user)
                                                        <option value="{{$user->id}}" {{$machine->regional_representative_id === $user->id ? 'selected' : ''}}>
                                                            @lang('transbaza_machine_edit.rp') #{{$user->id}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label for="type-account">
                                            <p>@lang('transbaza_machine_edit.promoter')</p>
                                            <div class="custom-select-exp">
                                                <select name="promoter_id">
                                                    <option value="" selected>Промоутер</option>
                                                    @foreach(\App\User::where('is_promoter', 1)->where('id', '!=', Auth::user()->id)->get() as $user)
                                                        <option value="{{$user->id}}" {{$machine->promoter_id === $user->id ? 'selected' : ''}}>
                                                            ПР #{{$user->id}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label>
                                            @lang('transbaza_machine_edit.sticker_promo_code')
                                            <input type="text" name="sticker_promo_code"
                                                   value="{{$machine->sticker_promo_code}}"
                                                   placeholder="Промо-код наклейки">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col roller-item">
                                        <div class="item">
                                            <i class="fas fa-plus active"></i>
                                            <i class="fas fa-minus"></i>
                                            <h4>@lang('transbaza_machine_edit.photo_with_sticker')</h4>
                                        </div>
                                        <div class="content">
                                            <helper-image-loader multiple-data="0" col-id="photoSticker"
                                                                 col-name="sticker"
                                                                 :url="{{json_encode(route('machinery.load-files'))}}"
                                                                 :exist="{{json_encode([$machine->sticker])}}"
                                                                 :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label>
                                            @lang('transbaza_machine_edit.certificate_number')
                                            <input type="text" name="act_number" value="{{$machine->act_number}}"
                                                   placeholder="@lang('transbaza_machine_edit.certificate_number')">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-item">
                                        <label>
                                            @lang('transbaza_machine_edit.psm_number')
                                            <input type="text" name="psm_number" placeholder="@lang('transbaza_machine_edit.psm_number')"
                                                   value="{{$machine->psm_number}}">
                                            <span class="error" style="display: none;">@lang('transbaza_machine_edit.unique_number')</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="col roller-item">
                                        <div class="item">
                                            <i class="fas fa-plus active"></i>
                                            <i class="fas fa-minus"></i>
                                            <h4>@lang('transbaza_machine_edit.documents_scan')</h4>
                                        </div>
                                        <div class="content">
                                            <helper-image-loader multiple-data="1" col-id="scsnsTech" col-name="scans[]"
                                                                 :url="{{json_encode(route('machinery.load-files'))}}"
                                                                 :exist="{{($machine->scans)}}"
                                                                 :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
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
                                                    <input type="text" name="year_release"
                                                           value="{{$machine->year_release}}"
                                                           placeholder="Год выпуска">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.owner')
                                                    <input type="text" name="owner" value="{{$machine->owner}}"
                                                           placeholder="@lang('transbaza_machine_edit.owner')">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    Выдано на основании
                                                    <input type="text" name="basis_for_witness"
                                                           value="{{$machine->basis_for_witness}}"
                                                           placeholder="Свидетельство выдано на основании">
                                                </label>
                                            </div>
                                            <div class="form-item image-item end">
                                                <label>
                                                    @lang('transbaza_machine_edit.certificate_date')
                                                    <input type="text" data-toggle="datepicker" name="witness_date"
                                                           value="{{$machine->witness_date}}"
                                                           placeholder="Дата свидетельства">
                                                    <span class="image date"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
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
                                                           value="{{$machine->psm_manufacturer_number}}"
                                                           placeholder="Заводской номер">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.engine')
                                                    <input type="text" name="engine"
                                                           value="{{$machine->engine}}" placeholder=" @lang('transbaza_machine_edit.engine') ">
                                                </label>
                                            </div>

                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.transmission')
                                                    <input type="text" name="transmission"
                                                           value="{{$machine->transmission}}"
                                                           placeholder="@lang('transbaza_machine_edit.transmission') ">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.leading_bridge')
                                                    <input type="text" name="leading_bridge"
                                                           value="{{$machine->leading_bridge}}"
                                                           placeholder=" @lang('transbaza_machine_edit.leading_bridge') ">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.color')
                                                    <input type="text" name="colour"
                                                           value="{{$machine->colour}}" placeholder=" @lang('transbaza_machine_edit.color') ">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.engine_type')
                                                    <input type="text" name="engine_type"
                                                           value="{{$machine->engine_type}}"
                                                           placeholder=" @lang('transbaza_machine_edit.engine_type') ">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.engine_power')
                                                    <input type="text" name="engine_power"
                                                           value="{{$machine->engine_power}}"
                                                           placeholder=" @lang('transbaza_machine_edit.engine_power') ">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.construction_weight')
                                                    <input type="text" name="construction_weight"
                                                           value="{{$machine->construction_weight}}"
                                                           placeholder=" @lang('transbaza_machine_edit.construction_weight') ">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.max_construction_speed')
                                                    <input type="text" name="construction_speed"
                                                           value="{{$machine->construction_speed}}"
                                                           placeholder=" @lang('transbaza_machine_edit.max_construction_speed') ">
                                                </label>
                                            </div>
                                            <div class="form-item">
                                                <label>
                                                    @lang('transbaza_machine_edit.dimensions')
                                                    <input type="text" name="dimensions"
                                                           value="{{$machine->dimensions}}"
                                                           placeholder=" @lang('transbaza_machine_edit.dimensions') ">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="col roller-item">
                        <div class="item">
                            <i class="fas fa-plus active"></i>
                            <i class="fas fa-minus"></i>
                            <h4>@lang('transbaza_machine_edit.terms_if_sale')</h4>
                        </div>
                        <div class="content">
                           {{-- <div class="col-md-4">
                                <div class="form-item">
                                    <label class="required">
                                        Spot цена (немедленная продажа)*
                                        <input type="text" id="spot_price" name="spot_price"
                                               placeholder="Введите сумму" >

                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item">
                                    <label class="required">
                                        Желаемая цена (сколько реально хочу за эту технику)*
                                        <input type="number" step="1" id="price" name="price"
                                               value=""
                                               placeholder="Введите сумму">
                                    </label>
                                </div>
                            </div>--}}

                            <div class="clearfix"></div>
                            <div class="col-md-4">
                                <div class="form-item">
                                    <label for="sale-input-1" class="checkbox">
                                        @lang('transbaza_machine_edit.publish_in_advert')
                                        <input type="checkbox"  class="sale_checks" name="advert_sale" value="1"
                                               id="sale-input-1" {{$machine->advert ? 'checked' : ''}}>
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label class="required">
                                        @lang('transbaza_machine_edit.sale_price') (руб.)
                                        <input type="text" step="1" id="advert_price" name="advert_price" class="money"
                                               value="{{$machine->advert ? $machine->advert->sum / 100 : ''}}"
                                               placeholder="Введите сумму">
                                    </label>
                                </div>
                                <div id="advert_url">
                                    @if($machine->advert)
                                        <a href="{!!  $machine->advert->url!!}" target="_blank">@lang('transbaza_machine_edit.advert_link') </a>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-item">
                                    <label for="sale-input-2" class="checkbox">
                                        @lang('transbaza_machine_edit.publish_in_sale')
                                        <input type="checkbox" class="sale_checks" name="all_sale" value="1"
                                               id="sale-input-2" {{$machine->sale ? 'checked' : ''}}>
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label class="required">
                                        @lang('transbaza_machine_edit.sale_price') (руб.)
                                        <input type="text" step="1" id="sale_price"  name="sale_price" class="money"
                                               value="{{$machine->sale ? $machine->sale->price / 100 : ''}}"
                                               placeholder="Введите сумму">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-item">
                                    <label for="sale-input-3" class="checkbox">
                                        @lang('transbaza_machine_edit.publish_in_auction')
                                        <input type="checkbox"  class="sale_checks" name="auction_sale" value="1"
                                               id="sale-input-3" {{$machine->auction ? 'checked' : ''}}>
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label class="required">
                                        @lang('transbaza_machine_edit.start_price') (руб.)
                                        <input type="text" step="1" id="auction_price" name="auction_price" class="money"
                                               value="{{$machine->auction ? $machine->auction->start_sum : ''}}"
                                               placeholder="Введите сумму">
                                    </label>
                                </div>
                                <div id="auction_url">
                                    @if($machine->auction)
                                        <a href="{!!  $machine->auction->url!!}" target="_blank">Перейти к аукциону</a>
                                    @endif
                                </div>
                                <div id="auction_fields"  @if(!$machine->auction) style="display: none" @endif >
                                    <b>@lang('transbaza_machine_edit.auction_type')</b>
                                    <div class="form-item">
                                        <label for="auction-input-1" class="radio">
                                            @lang('transbaza_machine_edit.auction_up')
                                            <input type="radio" name="auction_type" value="up"
                                                   id="auction-input-1" checked>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label for="auction-input-2" class="radio">
                                            @lang('transbaza_machine_edit.auction_down')
                                            <input type="radio" name="auction_type" value="down"
                                                   id="auction-input-2">
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-6">
                                <div class="form-item">
                                    <label class="required">
                                        @lang('transbaza_machine_edit.sale_description')
                                        <textarea id="description" name="description"
                                                  value="" style="height: auto;"
                                                  placeholder="Описание" rows="3"></textarea>
                                    </label>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-4">
                                <div class="button">
                                    <button class="btn-custom" type="button" id="publish_sale"
                                            data-url="{{route('publish_sale', $machine->id)}}">
                                        @lang('transbaza_machine_edit.publish')
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="hr-line"></div>
                <div class="button-wrap">
                    <div class="button two-btn">
                        <button class="btn-custom" type="submit">
                            @lang('transbaza_machine_edit.save')
                        </button>
                        <button class="btn-custom" id="reset-btn" type="reset" style="display: none;"> Отмена
                        </button>
                        <a href="/contractor/machinery" class="btn-custom">@lang('transbaza_machine_edit.cancel')</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @include('scripts.machine.edit')
@endsection
@push('after-scripts')
    <script>
        $(document).on('click', '#publish_sale', function (e) {
            e.preventDefault();

            let $button = $(this);
            let checks = []
            $('.sale_checks:checked').each(function () {
                checks.push($(this).attr('name'))
            });
            console.log(checks);
            $.ajax({
                url: $button.data('url'),
                type: 'POST',
                data: {
                    sale_type: $('input[name=sale_type]:checked').val(),
                    advert_price: $('#advert_price').val(),
                    sale_price: $('#sale_price').val(),
                    auction_price: $('#auction_price').val(),
                 //   price: $('#price').val(),
                    description: $('#description').val(),
                    checks: checks,
                    auction_type: $('[name=auction_type]:checked').val()

                },
                success: function (e) {
                    showMessage(e.message)
                    if(typeof e.advert_url !== 'undefined'){
                        let href = '<a href="' + e.advert_url + '" target="_blank">Перейти к объявлению</a>';
                        $('#advert_url').html(href)
                    }
                    if(typeof e.auction_url !== 'undefined'){
                        let href = '<a href="' + e.auction_url + '" target="_blank">Перейти к аукциону</a>';
                        $('#auction_url').html(href)
                    }
                    $('#machineEdit').submit()
                },
                error: function (e) {
                    showErrors(e)
                }
            })
        })

        $(document).on('change', '[name=auction_sale]', function (e) {
            if(this.checked){
                $('#auction_fields').show();
            }else {
                $('#auction_fields').hide();
            }
        })
        var __machine_page = 1;
        $(document).ready(function () {
            $(document).on('type type_eq', function (e, name, value, id) {
                $.ajax({
                    url: '{!! route('machine_option_fields') !!}',
                    type: 'GET',
                    data: {type_id: id},
                    success: function (e) {
                        $('#optional_fields').html(e.options)
                    },
                    error: function () {
                        $('#optional_fields').html('')
                    }

                })

            })
            $('.money').mask('000 000 000 000 000.00', {reverse: true});
            let masks = ['I000ZZ00Y', 'IIIIIIIII']
            let options = {
                translation: {
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
                    if(cep.match(/^[А-Яа-я](.*)?$/igm)){
                        $('.number').mask(masks[0], options)
                    }else {
                        $('.number').mask(masks[1], options)
                    }
                    if (cep.length >= 9) {
                        checkNumber(cep)
                    }
                },
            };
            $('.number').mask('IIIIIIIIIII', options)


        })
        $(document).on('change', '[name=machine_type]', function () {
            if ($(this).val() === 'equipment') {
                $('.number').closest('.col').hide();
                /*machine_select
                equipment_select*/
                $('#machine_select').hide();
                $('#equipment_select').show();
                $('.show-if-number').css('display', 'block')
            } else {
                $('#machine_select').show();
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
                url: '/check-number-machinery/{{$machine->id}}',
                data: data,
                success: function () {
                    $('.number').parent().find('.error').hide()
                },
                error: function (err) {
                    if (err.status == 400) {
                        $('.number').parent().find('.error').show()
                    }
                }
            })
        }
    </script>
@endpush
