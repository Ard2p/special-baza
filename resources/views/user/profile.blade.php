@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>@lang('transbaza_profile.profile_title')</h1></div>
        </div>
        <div class="row">
            <div class="col-md-3 col-xs-12"><!--left col-->

                @include('sections.info')

            </div><!--/col-3-->
            <div class="col-md-9 col-xs-12 user-profile-wrap box-shadow-wrap">
                @if(Session::has('email_verify'))
                    <div class="alert alert-danger">
                        {{implode(' ', Session::get('email_verify'))}}
                    </div>
                @endif
                @if(Session::has('email_confirm'))
                    <div class="alert alert-success">
                        {{Session::get('email_confirm')}}
                    </div>
                @endif
                <div id="tabs-panel" class="button search-btns">
                    <ul class="nav nav-tabs" id="myTab">
                        <li style="width: 50%"><a href="#profile" class="btn-custom black active show"
                                                  data-toggle="tab">@lang('transbaza_profile.tab_profile_title')</a></li>

                        <li style="width: 50%"><a href="#notifications" class="btn-custom"
                                                  data-toggle="tab">@lang('transbaza_profile.tab_notifications_title')</a></li>

                    </ul>

                    <div class="tab-content">

                        <div class="tab-pane active" id="profile">
                            <div class="col-md-offset-2 col-md-8">


                                {{--<h3>@lang('transbaza_profile.my')</h3>  --}}  <b class="float-right">@lang('transbaza_profile.your_id') {{Auth::user()->id}}</b>
                                <form class="form-horizontal" role="form" id="userForm" autocomplete="disabled">

                                    @csrf
                                    <div class="form-item  {{Auth::user()->email_confirm !== 1 ? '' : 'image-item end'}}">
                                        <label>@lang('transbaza_profile.email'):</label>
                                        <input value="{{Auth::user()->email}}" name="email" placeholder="@lang('transbaza_home.form_enter_email')"
                                               type="text" @if(Auth::user()->email) disabled="disabled" @endif >
                                        @if(Auth::user()->email_confirm !== 1)
                                            <span class="error">@lang('transbaza_profile.email_not_confirm') <a href="#"
                                                                                                 id="resendToken">@lang('transbaza_profile.resend_email')</a></span>
                                        @else
                                            <span class="image" style="opacity: 1;"> <i class="fa fa-check"
                                                                                        style="color: #33c733;margin-top: 15px;font-size: 20px;"></i></span>
                                        @endif
                                    </div>

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
                                    <helper-select-input :data="{{\App\Support\Country::all()}}"
                                                         :column-name="{{json_encode('Выберите страну')}}"
                                                         :place-holder="{{json_encode('Выберите страну')}}"
                                                         :col-name="{{json_encode('country_id')}}"
                                                         :required="1"
                                                         :initial="{{json_encode(Auth::user()->country)}}"
                                                         :show-column-name="1"></helper-select-input>
                                    <helper-select-input :data="{{$regions->toJson()}}"
                                                         :column-name="{{json_encode('Выберите регион')}}"
                                                         :place-holder="{{json_encode('Выберите регион')}}"
                                                         :col-name="{{json_encode('region')}}"
                                                         :hide-city="1"
                                                         :initial="{{json_encode($initial_region ?? '')}}">
                                    </helper-select-input>
                                    <helper-select-input :data="{{json_encode($cities_data)}}"

                                                         :place-holder="{{json_encode(trans('transbaza_machine_edit.city'))}}"
                                                         :hide-city="1"
                                                         :show-column-name="1"
                                                         :col-name="{{json_encode('city_id')}}"
                                                         :initial="{{json_encode(Auth::user()->city ?? '')}}">
                                    </helper-select-input>
                                    <div class="form-item {{Auth::user()->phone_confirm !== 1 ? '' : 'image-item end'}}">
                                        <label>@lang('transbaza_profile.phone')</label>
                                        <input name="phone" class="phone" value="{{Auth::user()->phone}}"
                                               type="text">
                                        @if(Auth::user()->phone_confirm !== 1)
                                            <span class="error">@lang('transbaza_profile.phone_not_confirm')<a href="#"
                                                                                                   data-url="{{route('resend_sms')}}"
                                                                                                   id="resendSms">@lang('transbaza_profile.send_sms_code')</a></span>
                                        @else
                                            <span class="image" style="opacity: 1;"> <i class="fa fa-check"
                                                                                        style="color: #33c733;margin-top: 15px;font-size: 20px;"></i></span>
                                        @endif
                                    </div>
                                    <div id="accept_sms_token">

                                    </div>
                                    <div class="form-item">
                                        <label>@lang('transbaza_profile.role')</label>
                                        <label for="checked-input" class="checkbox">
                                            @lang('transbaza_profile.customer')
                                            <input type="checkbox" name="customer" value="1"
                                                   id="checked-input" {{Auth::user()->checkRole('customer') ? 'checked' : ''}}>
                                            <span class="checkmark"></span>
                                        </label>

                                    </div>
                                    <div class="form-item">
                                        <label for="checked-input2" class="checkbox">
                                            @lang('transbaza_profile.contractor')
                                            <input type="checkbox" name="performer" value="1"
                                                   id="checked-input2" {{Auth::user()->checkRole('performer') ? 'checked' : ''}}>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label for="checked-input3" class="checkbox">
                                            @lang('transbaza_profile.widget')
                                            <input type="checkbox" name="widget" value="1"
                                                   id="checked-input3" {{Auth::user()->checkRole('widget') ? 'checked' : ''}}>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    @if(Auth::user()->isContractor())
                                        <div class="form-item">
                                            <label> @lang('transbaza_profile.params')</label>
                                            <label for="is_regional_representative" class="checkbox">
                                                @lang('transbaza_profile.regional_representative')
                                                <input type="checkbox" name="is_regional_representative" value="1"
                                                       id="is_regional_representative"
                                                       {{Auth::user()->is_regional_representative ? 'checked' : ''}} disabled>
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                        <div class="form-item">
                                            <label for="is_promoter" class="checkbox">
                                                @lang('transbaza_profile.promoter')
                                                <input type="checkbox" name="is_promoter" value="1"
                                                       id="is_promoter"
                                                       {{Auth::user()->is_promoter ? 'checked' : ''}} disabled>
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                        <div class="form-item">
                                            <label for="enable_ticker" class="checkbox">
                                                @lang('transbaza_profile.enable_ticker')
                                                <input type="checkbox" name="enable_ticker" value="1"
                                                       id="enable_ticker"
                                                       {{Auth::user()->enable_ticker ? 'checked' : ''}}>
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>

                                        <div class="form-item">
                                            <label> @lang('transbaza_profile.promo_code')</label>
                                            <input name="promo_code" class="promo_code"
                                                   value="{{Auth::user()->promo_code}}"
                                                   type="text" disabled>
                                        </div>
                                        <div class="form-item">
                                            <label> @lang('transbaza_profile.my_regional_representative')</label>
                                            <input class="promo_code"
                                                   value="{{Auth::user()->regional_representative->id_with_email ?? 'Не указан'}}"
                                                   type="text" disabled>
                                        </div>

                              {{--          <div class="form-item">
                                            <label for="checked-input-alias" class="checkbox">
                                                Включить публичную страницу
                                                <input type="checkbox" name="contractor_alias_enable" value="1"
                                                       id="checked-input-alias" {{Auth::user()->contractor_alias_enable ? 'checked' : ''}}>
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>--}}

                                        <div class="form-item">
                                            <label> @lang('transbaza_profile.publish_page_url')</label>
                                            <input class="promo_code"
                                                   value="{{Auth::user()->contractor_alias}}"
                                                   type="text" disabled>
                                        </div>

                                        @if(Auth::user()->contractor_alias_enable)
                                            <a href="{{route('user_public_page', Auth::user()->contractor_alias)}}"
                                               class="link-register">{{route('user_public_page', Auth::user()->contractor_alias)}}</a>
                                        @endif
                                        <hr>
                                    @endif

                                    <div class="form-group">
                                        <div class="col-md-offset-2 col-md-8">
                                            <div class="button two-btn">
                                                <input class="btn-custom" value="{{trans('transbaza_profile.save')}}" type="submit">
                                                <span></span>
                                                <input class="btn-custom" value="{{trans('transbaza_profile.cancel')}}" type="reset">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <div class="hr-line"></div>
                                @if(!Auth::user()->hasOnlyWidgetRole())
                                    <h3>Реквизиты</h3>
                                    <div class="button two-btn">
                                        @if(Auth::user()->getActiveRequisiteType() === 'entity' || !$requisite)
                                            <a href="#" class="btn-custom black" data-toggle="modal"
                                               data-target="#entityModal"> @lang('transbaza_profile.entity')</a>
                                        @endif
                                        @customer
                                        @if(Auth::user()->getActiveRequisiteType() === 'individual' || !$requisite)
                                            <a href="#individual" class="btn-custom black" data-toggle="modal"
                                               data-target="#individualModal"> @lang('transbaza_profile.individual')</a>
                                        @endif
                                        @endCustomer
                                    </div>
                                @endif
                                @if($requisite)
                                    <div class="requisite-list">
                                        <div class="item">
                                            <h3>
                                                @lang('transbaza_profile.requisites') {{Auth::user()->getActiveRequisiteType() === 'entity' ? 'ЮЛ' : 'ФЛ'}}</h3>
                                            <div class="button" style="width: 250px;margin: 0 auto;">
                                                <a href="#"
                                                   class="btn-custom {{Auth::user()->getActiveRequisiteType() === 'individual' ? 'deleteIndividual' : 'deleteEntity'}}"
                                                   data-id="{{$requisite->id}}"> @lang('transbaza_profile.delete_requisites')</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="hr-line"></div>
                                <h3> @lang('transbaza_profile.change_password')</h3>
                                <form id="changePassword" class="form-horizontal">
                                    @csrf
                                    <div class="form-item">
                                        <label> @lang('transbaza_profile.password')
                                            <input name="password" value="" type="password">
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label> @lang('transbaza_profile.confirm_password')
                                            <input name="password_confirmation" value="" type="password">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"></label>
                                        <div class="col-md-12">
                                            <div class="button two-btn">
                                                <input class="btn-custom" value="{{trans('transbaza_profile.submit_change_password')}}" type="submit">
                                                <span></span>
                                                <input class="btn-custom" value="{{trans('transbaza_profile.cancel_password')}}" type="reset">
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="hr-line"></div>
                                <div class="button col-md-8 col-md-offset-2">
                                    <a href="#" class="btn-custom" data-type="{{Auth::user()->is_freeze ? 1 : 0}}"
                                       id="freeze_account">{{ !Auth::user()->is_freeze ? trans('transbaza_profile.delete_my_account') : 'Разморозить аккаунт'}}</a>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="notifications">
                            <form id="history_filter">
                                <div class="search-wrap">
                                    <div class="detail-search">
                                        <div class="filter-list-wrap col-list two-cols">
                                            <div class="col col-long">
                                                <div class="form-item image-item end">
                                                    <label for="date-picker-balance-trans">
                                                        @lang('transbaza_profile.notifications_filter_from')
                                                        <input type="text" id="date-picker-balance-trans"
                                                               name="date_from"
                                                               data-toggle="datepicker"
                                                               placeholder="2018/08/08" autocomplete="off">
                                                        <span class="image date"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col col-long">
                                                <div class="form-item image-item end">
                                                    <label for="date-picker-balance-trans-end">
                                                        @lang('transbaza_profile.notifications_filter_to')
                                                        <input type="text" id="date-picker-balance-trans-end"
                                                               name="date_to"
                                                               data-toggle="datepicker"
                                                               placeholder="2018/08/08" autocomplete="off">
                                                        <span class="image date"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col col-long">
                                                <div class="form-item">
                                                    @lang('transbaza_profile.notifications_filter_type')
                                                    <div class="custom-select-exp">
                                                        <select name="type" id="">
                                                            <option value=""> @lang('transbaza_profile.notifications_filter_choose_type')</option>

                                                            <option value="email">Email</option>
                                                            <option value="sms">Sms</option>

                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- <div class="btn-col">
                                                 <div class="button">
                                                     <button type="button" id="transactions_reset" class="btn-custom">
                                                         Сброс
                                                     </button>
                                                 </div>

                                             </div>--}}
                                            <div class="btn-col">
                                                <div class="button">
                                                    <button type="submit" class="btn-custom"> @lang('transbaza_profile.notifications_filter_refresh')</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <table id="notification_history" class="table table-striped table-bordered"
                                   style="width:100%">
                                <thead>
                                <tr>
                                    <th> @lang('transbaza_profile.notifications_filter_table_name')</th>
                                    <th> @lang('transbaza_profile.notifications_filter_table_type')</th>
                                    <th> @lang('transbaza_profile.notifications_filter_table_date')</th>

                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
    @include('user.modals.requisites.create-entity')
    @include('user.modals.requisites.create-individual')
    @include('scripts.requisites.index')
    @include('scripts.profile.index')
@endsection

@push('after-scripts')
    <script src="/js/tables/dataTables.js"></script>
    <script src="/js/tables/tableBs.js"></script>
    <script>
        var load_url = "{{route('profile_index', ['get_notification_history' => 1])}}"
        var notification_history = $('#notification_history').DataTable({
            "ajax": load_url,
            "autoWidth": false,
            "ordering": false,
            "searching": false,
            "paging": false,
            "columns": [
                {
                    "data": "name",
                },
                {
                    "data": "type",

                },
                {
                    "data": "created_at",
                },

            ],
        })

        $(document).on('submit', '#history_filter', function (e) {
            e.preventDefault()
            notification_history.ajax.url(load_url +'&'+ $(this).serialize()).load()
        })

        $(document).ready(function () {



            $('#tabs-panel a').click(function () {
                $('#tabs-panel a').removeClass('black')
                $(this).addClass('black')
            })

            $('.edit-entity').click(function (e) {
                e.preventDefault();
                var entityId = $(this).data('id');
                var entities = {!! json_encode($entities ?? []) !!}
                entities.forEach(function (entity) {
                    if (entity.id == entityId) {
                        for (var key in entity) {
                            $('#entityForm [name="' + key + '"]').val(entity[key])
                        }
                        var hiddenInput = '<input type="hidden" name="entity_id" value="' + entity.id + '">'
                        $('#entityForm').append(hiddenInput)
                        $('#entityModal').modal('show')

                    }
                })
            })

            $('.edit-individual').click(function (e) {
                e.preventDefault();
                var individualId = $(this).data('id');
                var individuals = {!! json_encode($individuals ?? []) !!}
                individuals.forEach(function (individual) {
                    if (individual.id == individualId) {
                        console.log(individual)
                        for (var key in individual) {
                            $('#individualForm [name="' + key + '"]').val(individual[key])
                        }
                        var hiddenInput = '<input type="hidden" name="individual_id" value="' + individual.id + '">'
                        $('#individualForm').append(hiddenInput)
                        $('#individualModal').modal('show')

                    }
                })
            })

        })
    </script>
@endpush