@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>@lang('transbaza_machine_index.title')</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>
                <form id="machineFilterForm" method="POST">
                    @csrf

                    <div class="search-wrap user-profile-wrap box-shadow-wrap machineries-wrap">

                        @if($machines->count())
                            <h3>@lang('transbaza_machine_index.title')</h3>
                            <div class="button my-search">

                                <helper-search :search-data="{{$searches->toJson()}}"
                                               :save-btn-id="{{json_encode('#save')}}"
                                               :url="{{json_encode('/search_machine')}}"
                                               :parent-form="{{json_encode('#machineFilterForm')}}"></helper-search>
                            </div>
                            <div class="hr-line"></div>
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
                            <div class="machinery-filter-wrap">
                                <div class="tree-cols-list">
                                    <div class="col">
                                        <helper-select-input :data="{{$regions->toJson()}}"
                                                             :column-name="{{json_encode(trans('transbaza_machine_index.filter_region'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_machine_index.filter_region'))}}"
                                                             :col-name="{{json_encode('region')}}"
                                                             :required="0"
                                                             :initial="{{json_encode($initial_region ?? '')}}"
                                                             :show-column-name="1"
                                                             :hide-city="1">
                                        </helper-select-input>

                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{$cities_data->toJson() ?? json_encode([])}}"
                                                             :column-name="{{json_encode(trans('transbaza_machine_index.filter_city'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_machine_index.filter_city'))}}"
                                                             :col-name="{{json_encode('city_id')}}"
                                                             :required="0"
                                                             :initial="{{json_encode($checked_city_source ?? '')}}"
                                                             :show-column-name="1"
                                                             :hide-city="1">
                                        </helper-select-input>
                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{\App\Machines\Type::all()->toJson()}}"
                                                             :column-name="{{json_encode(trans('transbaza_machine_index.filter_category'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_machine_index.filter_category'))}}"
                                                             :col-name="{{json_encode('type')}}"
                                                             :show-column-name="1"
                                                             :initial="{{json_encode($initial_type ?? '')}}"></helper-select-input>
                                    </div>
                                </div>
                                <div class="four-cols-list">
                                    <div class="col">
                                        <helper-select-input :data="{{\App\Machines\Brand::all()->toJson()}}"
                                                             :column-name="{{json_encode(trans('transbaza_machine_index.filter_brand'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_machine_index.filter_brand'))}}"
                                                             :col-name="{{json_encode('brand')}}"
                                                             :show-column-name="1"
                                                             :initial="{{json_encode($initial_brand ?? '')}}"></helper-select-input>
                                    </div>
                                    <div class="col">
                                        <div class="form-item">
                                            <p class="name">@lang('transbaza_machine_index.filter_type')</p>
                                            <div class="custom-select-exp">
                                                <select name="ownership">
                                                    <option value="">@lang('transbaza_machine_index.filter_choose')</option>
                                                    <option value="owner">@lang('transbaza_machine_index.filter_type_own')</option>
                                                    <option value="regional">@lang('transbaza_machine_index.filter_type_reg')</option>
                                                    <option value="promoter">@lang('transbaza_machine_index.filter_type_prom')</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-item image-item end">
                                            <label for="date-picker-doc">
                                                @lang('transbaza_machine_index.filter_date_free')
                                                <input type="text" id="date-picker-doc" name="date"
                                                       data-toggle="datepicker"
                                                       placeholder="2018/08/08"
                                                       value="{{\Carbon\Carbon::now()->format('Y/m/d')}}"
                                                       autocomplete="off">
                                                <span class="image date"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-item">
                                            <label>@lang('transbaza_machine_index.filter_promo')</label>
                                            <input name="promo_code" class="promo_code" value=""
                                                   type="text">
                                        </div>
                                    </div>
                                </div>
                                <div class="four-cols-list">
                                    <div class="col">
                                        <helper-select-input :data="{{$users->toJson()}}"
                                                             :column-name="{{json_encode(trans('transbaza_machine_index.filter_contractor'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_machine_index.filter_contractor'))}}"
                                                             :col-name="{{json_encode('contractor')}}"
                                                             :show-column-name="1"
                                                             :initial="{{json_encode($initial_user ?? '')}}"></helper-select-input>
                                    </div>
                                    <div class="col">
                                        <div class="form-item"><label for="checked-input_" class="checkbox">
                                                @lang('transbaza_machine_index.filter_show_delete')
                                                <input type="checkbox" name="deleted" value="1" id="checked-input_"> <span class="checkmark"></span></label></div>
                                    </div>
                                </div>
                                <div class="bottom-list">
                                    <div class="form-item">
                                        <label>@lang('transbaza_machine_index.filter_status')</label>
                                        <label for="checked-input"
                                               class="checkbox"
                                               style="background: #0000ff; color: #ffffff; padding: 5px 30px;">
                                            @lang('transbaza_machine_index.filter_status_free')
                                            <input type="checkbox" name="free" value="1"
                                                   id="checked-input">
                                            <span class="checkmark" style="left: 7px"></span>
                                        </label>
                                        <label for="checked-input2"
                                               class="checkbox"
                                               style="background: #ffc0cb; color: #ffffff; padding: 5px 30px;">
                                            @lang('transbaza_machine_index.filter_status_busy')
                                            <input type="checkbox" name="busy" value="1"
                                                   id="checked-input2">
                                            <span class="checkmark" style="left: 7px"></span>
                                        </label>
                                        <label for="checked-input3"
                                               class="checkbox"
                                               style="background: #008000; color: #ffffff; padding: 5px 30px;">
                                            @lang('transbaza_machine_index.filter_status_order')
                                            <input type="checkbox" name="order" value="1"
                                                   id="checked-input3">
                                            <span class="checkmark" style="left: 7px"></span>
                                        </label>
                                        <label for="checked-input4"
                                               class="checkbox"
                                               style="background: #ffa500; color: #ffffff; padding: 5px 30px;">
                                            @lang('transbaza_machine_index.filter_status_reserve')
                                            <input type="checkbox" name="reserve" value="1"
                                                   id="checked-input4">
                                            <span class="checkmark" style="left: 7px"></span>
                                        </label>

                                    </div>
                                </div>

                                <div class="three-buttons button">
                                    <div class="btn-col">
                                        <div class="button">
                                            <button class="btn-custom black" type="submit">@lang('transbaza_machine_index.filter_search_btn')</button>
                                        </div>
                                    </div>
                                    <div class="btn-col">
                                        <div class="button">
                                            <button class="btn-custom black" id="save" type="button">@lang('transbaza_machine_index.filter_save_search_btn')
                                            </button>
                                        </div>
                                    </div>
                                    <div class="btn-col">
                                        <div class="button">
                                            <button class="btn-custom black" id="filter-reset">@lang('transbaza_machine_index.filter_search_reset')</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hr-line"></div>

                            <div class="header-machinery">
                                <h3>@lang('transbaza_machine_index.machine_list')</h3>
                                <div class="button"><a href="/contractor/machinery/create" class="btn-custom">@lang('transbaza_machine_index.add_machine')</a></div>
                            </div>
                            <div id="not_found"></div>
                            <div class="col col-long">
                                <div class="btn-col">
                                    <div class="image-load">
                                        <div class="button">
                                            <label style="    display: block;">
                                                <span class="btn-custom black"
                                                      style="padding: 0 20px;">Импорт техники</span>
                                                <input type="file" onchange="importVehicles()"
                                                       id="vehicle_file"
                                                       data-url="{{route('import_vehicles')}}"
                                                       style="display: none;">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col col-long">
                                <div class="btn-col button">
                                    <a class="btn-custom black" href="{{route('export_example')}}"
                                       style="padding: 0 20px;">Загрузить пример</a>
                                </div>
                            </div>
                            <div class="hr-line"></div>
                            <div class="table-responsive adaptive-table">
                                <table id="machines_table" class="table table-striped table-bordered adaptive-table"
                                       style="width:100%">
                                    <thead>
                                    <tr>
                                        <th style="text-align: center; min-width: 125px;"><em class="fa fa-cog"></em>
                                        <th>@lang('transbaza_machine_index.table_region')</th>
                                        <th>@lang('transbaza_machine_index.table_city')</th>
                                        <th>@lang('transbaza_machine_index.table_category')</th>
                                        <th>@lang('transbaza_machine_index.table_brand')</th>
                                        <th>@lang('transbaza_machine_index.table_address')</th>
                                        <th>@lang('transbaza_machine_index.table_type')</th>
                                        <th>@lang('transbaza_machine_index.table_owner')</th>
                                        <th>@lang('transbaza_machine_index.table_phone')</th>
                                        <th>@lang('transbaza_machine_index.table_balance')</th>

                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($machines as $machine)
                                        <tr>
                                            <td>

                                                @if($machine->user_id == Auth::user()->id)
                                                    <a class="btn-machinaries" data-toggle="tooltip" title="@lang('transbaza_machine_index.tip_show')"
                                                       href="/contractor/machinery/{{$machine->id}}"><i
                                                                class="fas fa-info-circle"></i></a>
                                                    <a class="btn-machinaries" data-toggle="tooltip"
                                                       title="@lang('transbaza_machine_index.tip_edit')"
                                                       href="/contractor/machinery/{{$machine->id}}/edit"><i
                                                                class="fas fa-file-signature"></i></a>
                                                    <a class="btn-machinaries delete" data-toggle="tooltip"
                                                       title="@lang('transbaza_machine_index.tip_delete')" data-id="{{$machine->id}}"><i
                                                                class="fas fa-trash"></i></a>
                                                @elseif($machine->regional_representative_id == Auth::user()->id)
                                                    <a class="btn-machinaries" data-toggle="tooltip" title="@lang('transbaza_machine_index.tip_show')"
                                                       href="/contractor/machinery/{{$machine->id}}"><i
                                                                class="fas fa-info-circle"></i></a>
                                                @endif

                                            </td>
                                            <td>{{$machine->region->name}}</td>
                                            <td>{{$machine->city->name}}</td>
                                            <td>{{$machine->_type->name}}</td>
                                            <td>{{$machine->brand->name}}</td>
                                            <td>{{$machine->address}}</td>
                                            <td>{{$machine->user_id == Auth::user()->id ? trans('transbaza_machine_edit.owner') : trans('transbaza_machine_edit.regional_representative')}}</td>

                                            <td>@if($machine->regional_representative_id == Auth::user()->id)
                                                    #{{$machine->user->id}}  {{$machine->user->email}}
                                                @endif
                                            </td>
                                            <td>@if($machine->regional_representative_id == Auth::user()->id)
                                                    <p class="phone">{{$machine->user->phone}}</p>
                                                @endif
                                            </td>
                                            <td>@if($machine->regional_representative_id == Auth::user()->id)
                                                    {{$machine->user->getBalance('contractor') / 100}} руб.
                                                @endif
                                            </td>

                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="list-proposals">
                                <h1>@lang('transbaza_machine_index.machine_list')</h1>
                                <div class="proposal-items">
                                    @foreach($machines as $machine)
                                        <div class="item">
                                            <p>
                                                <strong>@lang('transbaza_machine_index.table_region')</strong>{{$machine->region->name}}
                                            </p>
                                            <p>
                                                <strong>@lang('transbaza_machine_index.table_city')</strong>{{$machine->city->name}}
                                            </p>
                                            <p>
                                                <strong>@lang('transbaza_machine_index.table_category')</strong>{{$machine->_type->name}}
                                            </p>
                                            <p>
                                                <strong>@lang('transbaza_machine_index.table_brand')</strong>{{$machine->brand->name}}
                                            </p>
                                            <p>
                                                <strong>@lang('transbaza_machine_index.table_address')</strong>{{$machine->address}}
                                            </p>
                                            <p>
                                                <strong>@lang('transbaza_machine_index.table_owner')</strong> @if($machine->regional_representative_id == Auth::user()->id)
                                                    #{{$machine->user->id}}  {{$machine->user->email}}
                                                @endif
                                            </p>
                                            <p >
                                                <strong>@lang('transbaza_machine_index.table_phone')</strong>
                                                @if($machine->regional_representative_id == Auth::user()->id)
                                                    <span class="phone">{{$machine->user->phone}}</span>
                                                @endif
                                            </p>
                                            <p>
                                                <strong>Баланс</strong>@if($machine->regional_representative_id == Auth::user()->id)
                                                    {{$machine->user->getBalance('contractor') / 100}} руб.
                                                @endif
                                            </p>
                                            <div class="button">
                                                @if($machine->user_id == Auth::user()->id)
                                                    <a href="/contractor/machinery/{{$machine->id}}" class="btn-custom">@lang('transbaza_machine_index.tip_show')</a>
                                                    <a href="/contractor/machinery/{{$machine->id}}/edit"
                                                       class="btn-custom edit-entity">@lang('transbaza_machine_index.tip_edit')</a>
                                                    <a href="#" class="btn-custom delete"
                                                       data-id="{{$machine->id}}">@lang('transbaza_machine_index.tip_delete')</a>
                                                @elseif($machine->regional_representative_id == Auth::user()->id)
                                                    <a href="/contractor/machinery/{{$machine->id}}" class="btn-custom">@lang('transbaza_machine_index.tip_show')</a>
                                                @endif
                                            </div>
                                        </div>

                                    @endforeach

                                </div>
                            </div>
                        @else
                            <div class="not-found-wrap">
                                <h3>@lang('transbaza_machine_index.no_machines')</h3>
                                <div class="button">
                                    <a href="/contractor/machinery/create" class="btn-custom black">@lang('transbaza_machine_index.add_machine')</a>
                                </div>
                            </div>
                        @endif
                    </div>
                    <input type="hidden" name="filters" value="1">
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->

@endsection

@push('after-scripts')
    <script src="/js/tables/dataTables.js"></script>
    <script src="/js/tables/tableBs.js"></script>
    <script>


        $(document).ready(function () {
            $('#tabs-panel a').click(function () {
                $('#tabs-panel a').removeClass('black')
                $(this).addClass('black')
            })
            $(document).on('submit', '#machineFilterForm', function (e) {
                e.preventDefault();
                var params = $('#machineFilterForm').serialize();
                $.ajax({
                    url: '/contractor/machinery',
                    type: 'GET',
                    data: params,
                    success: function (response) {
                        $('tbody').html(response.table)
                        $('.proposal-items').html(response.mobile)
                    },
                    error: function (e) {
                        showErrors(e);
                    }
                })
                // machines.ajax.url('/contractor/machinery?filters=1&' + params).load();
            });
            $(document).on('click', '.delete', function (e) {
                if(!confirm('Удалить технику?')){
                    return false;
                }
                e.preventDefault();
                var id = $(this).data('id');
                var tr = $(this).closest('tr')
                $.ajax({
                    url: '/contractor/machinery/' + id,
                    type: 'DELETE',
                    data: {_token: '{{csrf_token()}}'},
                    success: function (e) {
                        showMessage(e.message);
                        tr.remove()
                    },
                    error: function (e) {
                        showErrors(e);
                    }

                })
            });
            // $('body').on('click', '#save', function (e) {
            //     e.preventDefault();
            //     $('#alerts').hide();
            //
            //     var result = prompt('Введите название поиска.');
            //     if (result == null) {
            //         return;
            //     }
            //
            //     var data = $('#machineFilterForm').serialize() + '&name=' + result;
            //     $.ajax({
            //         url: '/search_machine',
            //         type: 'POST',
            //         data: data,
            //         success: function (response) {
            //             showMessage('Поиск создан')
            //             $('#search-selects').html(response.view);
            //             initSelects(true)
            //         },
            //         error: function (e) {
            //             showModalErrors(e);
            //         }
            //     })
            // })
            $(document).on('click', '#filter-reset', function (e) {
                e.preventDefault();
                location.reload();
                // machines.ajax.url('/contractor/machinery').load();
            })


        })


    </script>
@endpush