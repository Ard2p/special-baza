@extends('layouts.main')

@section('content')
    <div class="row">
        <div class="col-md-offset-4 col-md-4">
                <form method="post" action="#" class="register-form">
                    @csrf
                    <h2 class="title">@lang('transbaza_register.title')</h2>
                    <input type="hidden" id="_all_countries" value="{{json_encode($countries = \App\Support\Country::with('phone_masks')->get())}}">
                    <helper-select-input :data="{{$countries}}"
                                         :column-name="{{json_encode(trans('transbaza_register.choose_country'))}}"
                                         :place-holder="{{json_encode(trans('transbaza_register.choose_country'))}}"
                                         :col-name="{{json_encode('country_id')}}"
                                         :required="1"
                                         :initial="{{json_encode(\App\Support\Country::Russia())}}"
                                         :show-column-name="1"></helper-select-input>
                    <helper-select-input :data="{{$regions->toJson()}}"
                                         :column-name="{{json_encode('Выберите регион')}}"
                                         :place-holder="{{json_encode('Выберите регион')}}"
                                         :col-name="{{json_encode('region_id')}}"
                                         :hide-city="1"
                                         :initial="{{json_encode($initial_region ?? '')}}">
                    </helper-select-input>
                    <helper-select-input :data="{{json_encode([])}}"

                                         :place-holder="{{json_encode(trans('transbaza_machine_edit.city'))}}"
                                         :hide-city="1"
                                         :show-column-name="1"
                                         :col-name="{{json_encode('city_id')}}"
                                         :initial="{{json_encode('')}}">
                    </helper-select-input>
                    <div class="form-item image-item">
                        <label for="" class="required">
                            @lang('transbaza_register.your_email')
                            <input type="email" name="email" placeholder="@lang('transbaza_register.enter_email')">
                            <span class="image email"></span>
                        </label>
                        <span class="error"></span>
                    </div>
                    <div class="form-item image-item">
                        <label class="required">
                            @lang('transbaza_register.phone_number')
                            <input type="text" class="phone" name="phone" id="register_phone" placeholder="  @lang('transbaza_register.phone_number')"/>
                            <span class="image phone"></span>
                        </label>
                    </div>
                  {{--  <div class="form-item image-item">
                        <label for="type-account" class="required">
                            Тип аккаунта
                            <div class="custom-select-exp">
                                <select name="account_type">
                                    <option value="">Выберите тип</option>
                                    <option value="contractor">Исполнитель</option>
                                    <option value="customer">Заказчик</option>
                                </select>
                            </div>
                            <span class="image user"></span>
                        </label>
                    </div>--}}
                    @php
                     $roles = collect([
                     [
                      'id' => 'contractor',
                     'name' => trans('transbaza_roles.contractor')

                     ],
                     [
                     'id' => 'customer',
                     'name' => trans('transbaza_roles.customer')
                     ]

                     ])
                    @endphp
                    <helper-select-input :data="{{$roles->toJson()}}"
                                         :column-name="{{json_encode(trans('transbaza_register.account_type'))}}"
                                         :place-holder="{{json_encode(trans('transbaza_register.choose_account_type'))}}"
                                         :col-name="{{json_encode('account_type')}}"
                                         :required="1"
                                         :initial="{{json_encode([])}}"
                                         :show-column-name="1"></helper-select-input>
                    <div class="form-item image-item">
                        <label for="" class="required">
                           @lang('transbaza_register.enter_password')
                            <input type="password" name="password" placeholder=" @lang('transbaza_register.enter_password')">
                            <span class="image lock"></span>
                        </label>
                    </div>

                    <div class="form-item image-item">
                        <label for="" class="required">
                            @lang('transbaza_register.confirm_password')
                            <input type="password" name="password_confirmation" placeholder=" @lang('transbaza_register.confirm_password')">
                            <span class="image lock"></span>
                        </label>
                    </div>
                    <div class="form-item">
                        <label for="checked-input" class="checkbox">
                            @lang('transbaza_register.accept_personal')
                            <input type="checkbox" name="accept_personal" value="1"
                                   id="checked-input">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                    <div class="form-item">
                        <label for="checked-input2" class="checkbox">
                            <a href="{{ '/' . App\Content\StaticContent::find(6)->alias ?? '#'}}"> @lang('transbaza_register.accept_rules')</a>
                            <input type="checkbox" name="accept_rules" value="1"
                                   id="checked-input2">
                            <span class="checkmark"></span>
                        </label>

                    </div>
                    <a href="/login" class="link-register"> @lang('transbaza_register.auth_if_register')</a>

                    <div style="margin-left: 15px;">
                        {!! NoCaptcha::display() !!}
                    </div>
                    <div class="form-item">
                        <input type="hidden" name="captcha_error">
                    </div>
                    <div class="clearfix"></div>
                    <div class="button">
                        <button type="button" id="register_btn" class="btn-custom">
                            @lang('transbaza_register.register_button')
                        </button>
                    </div>
                    <div class="button">
                        <a href="{{route('facebook_redirect')}}" class="btn-custom black" style="position: relative;font-size: 12px;margin-top: 10px;"><img style="width: 32px;position: absolute;left: 2px;" src="/images/social/fb.png"> @lang('transbaza_register.fb_login')</a>
                        <a href="{{route('vkontakte_redirect')}}" class="btn-custom black" style="position: relative;font-size: 12px;margin-top: 10px;"><img style="width: 32px;position: absolute;left: 2px;" src="/images/social/vk.png">  @lang('transbaza_register.vk_login')</a>
                    </div>
                </form>

        </div>
    </div>
    @include('scripts.register')
@endsection
