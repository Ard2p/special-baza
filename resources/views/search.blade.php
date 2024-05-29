@extends('layouts.main')
@section('content')

    <div class="search-wrap">

        <div id="tab1" class="active tab-list">
            <div class="title-wrap">
                <h1 class="title" style="    font-size: 30px;
    font-weight: bold; margin-bottom: 0px">@lang('transbaza_proposal_search.search_title')</h1>

                {{--    <div class="button my-search">

                        <helper-search :search-data="{{$searches->toJson()}}"
                                       :parent-form="{{json_encode('#search-form')}}"
                                       :save-btn-id="{{json_encode('#save')}}"
                                       :url="{{json_encode('/search')}}"></helper-search>
                        <a href="/search?list" class="btn-custom black">
                            <i class="arrow-right"></i>
                        </a>
                    </div>--}}
            </div>
            <div class="detail-search" id="filter">

                <div class="alert alert-danger" id="alerts" style="display: none" role="alert">
                </div>
                <form action="post" id="search-form">
                    @csrf
                    <div id="searchWizard">
                        <ul>
                            <li><a href="#step-1">@lang('transbaza_proposal_search.step') 1<br/>
                                    <small>@lang('transbaza_proposal_search.what_need')</small>
                                </a></li>
                            <li><a href="#step-2">@lang('transbaza_proposal_search.step') 2<br/>
                                    <small>@lang('transbaza_proposal_search.second_step')</small>
                                </a></li>
                            <li><a href="#step-3">@lang('transbaza_proposal_search.step') 3<br/>
                                    <small>@lang('transbaza_proposal_search.category_budget')</small>
                                </a></li>
                            <li><a href="#step-4">@lang('transbaza_proposal_search.step')<br/>
                                    <small>@lang('transbaza_proposal_search.result')</small>
                                </a></li>

                        </ul>


                        <div id="step-1" class="text-center" style="max-height: 200px !important;">
                            <h3>@lang('transbaza_proposal_search.what_you_need')</h3>
                            <div style="display: block;">
                                <div class="col-md-6">
                                    <div class="button" style="padding: 5px">
                                        <button type="button"
                                                class="btn-custom"

                                                id="simple_step"
                                                style="background:  #1E6F41;">@lang('transbaza_proposal_search.simple_order')
                                            <br>
                                            @lang('transbaza_proposal_search.one_machine')
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="button" style="padding: 5px">
                                        <button class="btn-custom " type="button" style="background: #ee2b24"
                                                id="big_step">@lang('transbaza_proposal_search.big_order')
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="step-2" style="display: none" class="text-center row">

                            <h3>@lang('transbaza_proposal_search.address_start_date')</h3>


                            <div class="col-md-12" style="display: block;">
                                @php
                                    $region = \App\Support\Region::find(Auth::user()->native_region_id);
                                                   if ($region) {
                                                       $initial_region = ['id' => $region->id, 'name' => $region->full_name];
                                                       $cities_data = $region->cities;
                                                       $checked_city = \App\City::find(Auth::user()->native_city_id);
                                                       if ($checked_city) {
                                                            $checked_city_source = ['id' => $checked_city->id, 'name' => $checked_city->with_codes, 'full' => $checked_city->name];
                                                       }
                                                   }else{
                                                     $cities_data = [];
                                                     }
                                @endphp
                                <div class="col-md-6">
                                    <helper-select-input :data="{{json_encode(\App\Support\Country::all())}}"
                                                         :column-name="{{json_encode(trans('transbaza_proposal_search.choose_country'))}}"
                                                         :place-holder="{{json_encode(trans('transbaza_proposal_search.choose_country'))}}"
                                                         :col-name="{{json_encode('country_id')}}"
                                                         :required="1"
                                                         :initial="{{json_encode(Auth::user()->country)}}"
                                                         :show-column-name="1"></helper-select-input>
                                </div>
                                <div class="col-md-6">
                                    <helper-select-input :data="{{$regions->toJson()}}"
                                                         :column-name="{{json_encode(trans('transbaza_proposal_search.region'))}}"
                                                         :place-holder="{{json_encode(trans('transbaza_proposal_search.choose_region'))}}"
                                                         :col-name="{{json_encode('region')}}"
                                                         :required="1"
                                                         :initial="{{json_encode($initial_region ?? '')}}"
                                                         :initial-city="{{json_encode($checked_city_source ?? '')}}"
                                                         :city-data="{{json_encode($cities_data)}}"
                                                         :show-column-name="1"
                                                         :hide-city="1">
                                    </helper-select-input>
                                </div>
                                <div class="col-md-6">
                                    <helper-select-input :data="{{$cities_data->toJson()}}"
                                                         :column-name="{{json_encode(trans('transbaza_proposal_search.city'))}}"
                                                         :place-holder="{{json_encode(trans('transbaza_proposal_search.city'))}}"
                                                         :required="1"
                                                         :col-name="{{json_encode('city_id')}}"
                                                         :initial="{{json_encode($checked_city_source ?? '')}}"
                                                         :show-column-name="1"
                                                         :hide-city="1"></helper-select-input>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-item">
                                        <label for="address" class="required">
                                            @lang('transbaza_proposal_search.address')
                                            <textarea name="address" id="address" style="height: auto"
                                                      placeholder="@lang('transbaza_proposal_search.address')">{{$region->name}} {{$checked_city_source['full']}}</textarea>
                                        </label>
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                                <div class="col-md-4 col-xs-12">
                                    <div class="form-item image-item end">
                                        <label for="date" class="required">
                                            {{--Дата и время выполнения работ--}}
                                            @lang('transbaza_proposal_search.start_date')
                                            <input type="text" id="start_proposal" name="date"
                                                   placeholder="" autocomplete="off"
                                                   value="{{\Carbon\Carbon::now()->addDay()->startOfDay()->addHours(8)->format('Y/m/d')}}">

                                            <span class="image date"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4 col-xs-12">
                                    <div class="form-item">
                                        <label for="date" class="required">
                                            {{--Дата и время выполнения работ--}}
                                            @lang('transbaza_proposal_search.machine_to_time')
                                            <input type="text" data-toggle="_timepicker" name="time"
                                                   placeholder="" autocomplete="off"
                                                   value="{{\Carbon\Carbon::now()->addDay()->startOfDay()->addHours(8)->format('H:i')}}">

                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xs-12">
                                    <div class="form-item image-item end">
                                        <label for="date" class="required">
                                            {{--Дата и время выполнения работ--}}
                                            @lang('transbaza_proposal_search.date_end')
                                            <input type="text" id="end_proposal" name="date_end"
                                                   placeholder="" autocomplete="off"
                                                   value="{{\Carbon\Carbon::now()->addDay()->startOfDay()->addHours(8)->format('Y/m/d')}}">

                                            <span class="image date"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xs-12">

                                    <div class="form-item small">
                                        <label for="amount" class="required">
                                            @lang('transbaza_proposal_search.days')
                                            <input type="number" min="1" step="1" name="days" id="shifts" value="1"
                                                   placeholder=" @lang('transbaza_proposal_search.shift_number')"
                                                   disabled="disabled">
                                        </label>
                                    </div>
                                </div>

                                <div class="clearfix"></div>
                                <div class="col-md-6">
                                    <div class="button">
                                        <button class="btn-custom black" type="button" style="margin-top: 26px;"
                                                onclick="prevStep()"> {{'<<'}} @lang('transbaza_proposal_search.back')
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="button">
                                        <button class="btn-custom black" type="button" style="margin-top: 26px;"
                                                onclick="simpleFirst()">@lang('transbaza_proposal_search.forward') {{'>>'}}
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="step-3" class="row text-center" style="display: none">
                            <h3>@lang('transbaza_proposal_search.category_budget')</h3>
                            <div class="button" style="padding: 5px">
                                <button type="button"
                                        class="btn-custom black" id="search-btn" onclick="prevStep()"
                                > {{'<<'}} @lang('transbaza_proposal_search.back')
                                </button>
                            </div>
                            <div class="col-md-12" style="display: block">
                                <machines_container
                                        :data="{{\App\Machines\Type::whereType('machine')->get()->toJson()}}"
                                        :equipments-data="{{\App\Machines\Type::whereType('equipment')->get()->toJson()}}"
                                        :brand-data="{{\App\Machines\Brand::all()->toJson()}}"
                                        :column-name="{{json_encode(trans('transbaza_proposal_search.machine_category_label'))}}"
                                        :place-holder="{{json_encode(trans('transbaza_proposal_search.machine_category_choose'))}}"
                                        :col-name="{{json_encode('type')}}"
                                        :initial="{{json_encode($initial_type ?? '')}}"
                                        :show-column-name="1"></machines_container>

                                <div class="clearfix"></div>
                                <h4 class="text-center">@lang('transbaza_proposal_search.filter_by_cost')</h4>
                                <div class="col-md-6">

                                    <div class="col-md-12 col-xs-12">
                                        <div class="form-item">
                                            <label for="radio-input-1" class="radio">
                                                @lang('transbaza_proposal_search.per_hour')
                                                <input type="radio" name="sum_filter" value="machine" id="radio-input-1"
                                                       checked>
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-xs-12">
                                        <div class="form-item">
                                            <label for="radio-input-2" class="radio">
                                                @lang('transbaza_proposal_search.per_day')
                                                <input type="radio" name="sum_filter" value="equipment"
                                                       id="radio-input-2">
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-xs-12">
                                        <div class="form-item">
                                            <label for="radio-input-3" class="radio">
                                                @lang('transbaza_proposal_search.per_order')
                                                <input type="radio" name="sum_filter" value="equipment"
                                                       id="radio-input-3">
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="col-md-6">

                                    <div class="form-item small">
                                        <label for="price">
                                            @lang('transbaza_proposal_search.sum_to')
                                            <input type="text" id="price" name="sum" value="0"
                                                   placeholder="@lang('transbaza_proposal_search.sum')">
                                        </label>
                                    </div>


                                </div>
                                {{-- <div class="col-md-4">
                                     <div class="form-item small">
                                         <label>

                                             <input type="text" name="sum_hour" placeholder="Цена за час">
                                         </label>
                                     </div>
                                 </div>--}}
                                {{--  <div class="col-md-4">
                                      <div class="form-item small">
                                          <label>
                                              за день (смену)
                                              <input type="text" name="sum_day" placeholder="Цена за смену">
                                          </label>
                                      </div>
                                  </div>--}}


                                <div class="col-md-12">
                                    <div class="col-md-6">
                                        <div class="button">
                                            <button class="btn-custom black" type="button" style="margin-top: 26px;"
                                                    onclick="prevStep()">{{'<<'}} @lang('transbaza_proposal_search.back')
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="button">
                                            <button type="submit"
                                                    class="btn-custom" id="search-btn"
                                                    style="background:  #ff1411;margin-top: 26px;"><i
                                                        class="fa fa-search"></i>&nbsp;@lang('transbaza_proposal_search.search')
                                            </button>
                                        </div>
                                    </div>
                                    {{--  <div class="col-list">
                                          <div class="bottom-cols">

                                              <div class="col-small">
                                                  <div class="form-item btn-has">
                                                      <button id="save" type="button" class="save"></button>
                                                  </div>
                                                  <div class="form-item btn-has">
                                                      <button id="delete" type="button" class="trash" onclick="
                                      $('#search-form')[0].reset();
                                      $('#search-form textarea').text('');
                                      "></button>
                                                  </div>
                                                  <div class="form-item btn-has">
                                                      <button id="search-btn" class="search" ></button>
                                                  </div>
                                              </div>

                                          </div>

                                      </div>--}}
                                </div>

                            </div>
                        </div>
                        <div id="step-4" class="row" style="display: none">
                            <div id="searchResultsBlock" style="display: none">
                                {{--<div class="map-wrap">
                                    <div class="button">
                                        <a href="#" data-id="tab" class="btn-custom black">Таблица</a>
                                        <a href="#" data-id="map" class="btn-custom">Карта</a>
                                    </div>
                                    <div id="tabmap" class="tab-list-map">
                                        <div id="map" class="row clearfix body" style="height: 425px;"></div>
                                    </div>
                                    <div id="tabtab" class="active">

                                    </div>
                                </div>--}}
                                <ul id="myTab" class="nav nav-tabs">
                                    <li style="width: 50%;"><a href="#tabtab" data-toggle="tab"
                                                               class=" active show">@lang('transbaza_proposal_search.tab_show_list')</a>
                                    </li>
                                    <li style="width: 50%;"><a href="#tabmap"
                                                               data-toggle="tab">@lang('transbaza_proposal_search.tab_show_map')</a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="tabtab"></div>
                                    <div class="tab-pane" id="tabmap">
                                        <div id="map" class="row clearfix body" style="height: 425px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="not_found"></div>

                            <div class="col-md-6" style="display: block">
                                <div class="button">
                                    <button class="btn-custom black" type="button" style="margin-top: 26px;"
                                            onclick="prevStep()"> {{'<<'}} @lang('transbaza_proposal_search.back')
                                    </button>
                                </div>
                            </div>
                        </div>


                    </div>
                </form>


            </div>
        </div>

        <div id="tab2" class="tab-list">
            <div class="title-wrap">
                <h1 class="title">Заявки</h1>
                <div class="button my-search">
                    <a href="#" class="btn-custom black">@lang('transbaza_proposal_search.my_proposals')<i
                                class="arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div id="tab3" class="tab-list">
            <div class="title-wrap">
                <h1 class="title">Заказы</h1>
                <div class="button my-search">
                    <a href="#" class="btn-custom black">@lang('transbaza_proposal_search.my_orders') <i
                                class="arrow-right"></i></a>
                </div>
            </div>
        </div>


    </div>
    <form id="proposal_form" action="/new-proposal" method="POST" style="display: none"></form>
    <div class="modal modal-fade" id="machine-modal" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                                class="sr-only">Close</span></button>
                    <h4 class="modal-title">
                        @lang('transbaza_proposal_search.machine_card')
                    </h4>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal">@lang('transbaza_proposal_search.modal_close')</button>

                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-fade" id="hold-modal" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                                class="sr-only">Закрыть</span></button>
                    <h4 class="modal-title">
                        @lang('transbaza_proposal_search.reserve_sum')
                    </h4>
                </div>
                <div class="modal-body">
                    <form id="hold_it" method="post" action="{{route('hold_for_proposal')}}">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal">@lang('transbaza_proposal_search.modal_close')</button>

                </div>
            </div>
        </div>
    </div>

    @include('scripts.search.search')
@endsection

@push('after-scripts')
    <script>
        var state = {
            current: ''
        };

        Object.defineProperty(state, "_current", {

            get: function () {
                return this.current
            },

            set: function (value) {
                if (value === 'simple') {
                    $(document).trigger('current_search', 'simple')
                } else {
                    $(document).trigger('current_search', 'big')
                }
                this.current = value;
            }
        });
        var wizzard = $('#searchWizard').smartWizard({
            selected: 0,
            theme: 'default',
            transitionEffect: 'fade',
            showStepURLhash: false,
            contentCache: false,
            keyNavigation: false,
            /*  hiddenSteps: [2,3],*/
            lang: {
                next: 'Далее',
                previous: 'Назад'
            },
            toolbarSettings: {
                toolbarPosition: 'none'
            },
            anchorSettings: {
                anchorClickable: false

            }

        });
        $("#searchWizard").on("showStep", function (e, anchorObject, stepNumber, stepDirection) {
            let step = stepNumber + 1;
            var width = $(window).width();
            setTimeout(function () {
                $(document).find("#step-1").css('min-height', '200px')
            }, 500)

            if (width <= 780) {
                $([document.documentElement, document.body]).animate({
                    scrollTop: $(document).find("#step-" + step).offset().top
                }, 500);
            }
        });

        $('#simple_step').click(function () {
            state._current = 'simple';
            wizzard.smartWizard('next');
        })
        $('#big_step').click(function () {
            state._current = 'big';
            wizzard.smartWizard('next');
        })

        function simpleFirst() {
            var url = "{{route('validate_simple_step', 1)}}";
            $.ajax({
                url: url,
                type: 'POST',
                data: $('#search-form').serialize(),
                success: function () {
                    wizzard.smartWizard('next');
                },
                error: function (e) {
                    showErrors(e)
                }
            })
        }

        function nextStep() {
            wizzard.smartWizard('next');
        }

        function prevStep() {
            wizzard.smartWizard('prev');
        }
        jQuery('#start_proposal').datetimepicker({
            format: 'Y/m/d',
            dayOfWeekStart: 1,
            onShow: function (ct) {
                this.setOptions({
                    minDate: "{{\Carbon\Carbon::now()->addDay()->startOfDay()->addHours(8)->format('Y/m/d')}}"
                })
            },
            onChangeDateTime: function () {
                changeDate()
            },
            timepicker: false
        });
        jQuery('#end_proposal').datetimepicker({
            format: 'Y/m/d',
            dayOfWeekStart: 1,
            onShow: function (ct) {
                this.setOptions({
                    minDate: jQuery('#start_proposal').val() ? jQuery('#start_proposal').val() : false
                })
            },
            onChangeDateTime: function () {
                changeDate()
            },
            timepicker: false
        });

        function inDays(d1, d2) {
            var t2 = d2.getTime();
            var t1 = d1.getTime();

            let val = parseInt((t2 - t1) / (24 * 3600 * 1000)) + 1;
            if (val < 0) {
                jQuery('#end_proposal').val(dateFormat(d1, 'yyyy/mm/dd'));
                return inDays(d1, d1)
            }
            return val;
        }

        function changeDate() {
            let d1 = new Date(jQuery('#start_proposal').val());
            let d2 = new Date(jQuery('#end_proposal').val());
            $('#shifts').val(inDays(d1, d2))
        }

        $(document).on('click', '.create_order', function () {
            var button = $(this);
            var request = button.data('search');
            var user_id = button.data('user-id');
            var machines = button.data('machine-ids');
            button.hide();

            if (!confirm("Создать заказ?")) {
                return;
            }
            $.ajax({
                url: '{{route('invite')}}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    user_id: user_id,
                    request: request,
                    machines: machines,
                },
                success: function (message) {
                    showMessage('Заказ создан.')
                },
                error: function (e) {
                    if (typeof e.responseJSON.hold !== 'undefined') {
                        $('#hold_it').html(e.responseJSON.hold);
                        $('#hold-modal').modal('show');
                        delete e.responseJSON.hold;
                    }
                    button.show();
                    showModalErrors(e);
                }

            })
        })
        $(document).on('submit', '#hold_it', function (e) {
            e.preventDefault();
            let $form = $(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function (e) {
                    location.href = e.formUrl
                },
                error: function () {
                    showErrors(e)
                }
            })
        });
    </script>
    <style>
    </style>
@endpush