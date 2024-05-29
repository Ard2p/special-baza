@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>@lang('transbaza_contractor_services.title')</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>
                <form id="serviceFilterForm" method="POST">
                    @csrf
                    <div class="search-wrap user-profile-wrap box-shadow-wrap machineries-wrap">
                        @if($services->count())
                            <h3>@lang('transbaza_contractor_services.filter')</h3>

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
                                        <helper-select-input :data="{{$categories->toJson()}}"
                                                             :column-name="{{json_encode(trans('transbaza_contractor_services.filter_category'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_contractor_services.filter_category'))}}"
                                                             :col-name="{{json_encode('category_id')}}"
                                                             :required="0"

                                                             :show-column-name="1"
                                                             :hide-city="1">
                                        </helper-select-input>

                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{$regions->toJson()}}"
                                                             :column-name="{{json_encode(trans('transbaza_contractor_services.filter_region'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_contractor_services.filter_region'))}}"
                                                             :col-name="{{json_encode('region')}}"
                                                             :required="0"
                                                             :initial="{{json_encode($initial_region ?? '')}}"
                                                             :show-column-name="1"
                                                             :hide-city="1">
                                        </helper-select-input>

                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{$cities_data->toJson() ?? json_encode([])}}"
                                                             :column-name="{{json_encode(trans('transbaza_contractor_services.filter_city'))}}"
                                                             :place-holder="{{json_encode(trans('transbaza_contractor_services.filter_city'))}}"
                                                             :col-name="{{json_encode('city_id')}}"
                                                             :required="0"
                                                             :initial="{{json_encode($checked_city_source ?? '')}}"
                                                             :show-column-name="1"
                                                             :hide-city="1">
                                        </helper-select-input>
                                    </div>
                                </div>
                                <div class="three-buttons button">
                                    <div class="btn-col">
                                        <div class="button">
                                            <button class="btn-custom black" type="submit">@lang('transbaza_contractor_services.filter_search')</button>
                                        </div>
                                    </div>
                                    <div class="btn-col">
                                        <div class="button">
                                            <button class="btn-custom black" id="filter-reset">@lang('transbaza_contractor_services.filter_reset')</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hr-line"></div>
                            <div class="header-machinery">
                                <h3>@lang('transbaza_contractor_services.services_list')</h3>
                                <div class="button"><a href="{{route('my-services.create')}}" class="btn-custom">@lang('transbaza_contractor_services.add_service')</a></div>
                            </div>
                            <div id="not_found"></div>
                            <div class="table-responsive adaptive-table">
                                <table id="machines_table" class="table table-striped table-bordered adaptive-table"
                                       style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>@lang('transbaza_contractor_services.table_name')</th>
                                        <th>@lang('transbaza_contractor_services.table_region')</th>
                                        <th>@lang('transbaza_contractor_services.table_city')</th>
                                        <th style="text-align: center; min-width: 125px;"><em class="fa fa-cog"></em>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($services as $service)
                                        <tr>
                                            <td>{{$service->name}}</td>
                                            <td>{{$service->region->name}}</td>
                                            <td>{{$service->city->name}}</td>
                                            <td>


                                                    <a class="btn-machinaries" data-toggle="tooltip" title="@lang('transbaza_contractor_services.table_show')"
                                                       href="{{route('my-services.show', $service->id)}}"><i
                                                                class="fas fa-info-circle"></i></a>
                                                    <a class="btn-machinaries" data-toggle="tooltip"
                                                       title="@lang('transbaza_contractor_services.table_edit')"
                                                       href="{{route('my-services.edit', $service->id)}}"><i
                                                                class="fas fa-file-signature"></i></a>
                                                    <a class="btn-machinaries delete" data-toggle="tooltip"
                                                       title="@lang('transbaza_contractor_services.table_delete')" data-id="{{$service->id}}"><i
                                                                class="fas fa-trash"></i></a>

                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="list-proposals">
                                <h1>@lang('transbaza_contractor_services.services_list')</h1>
                                <div class="proposal-items">
                                    @foreach($services as $service)
                                        <div class="item">
                                            <p>
                                                <strong>@lang('transbaza_contractor_services.table_name')</strong>{{$service->region->name}}
                                            </p>
                                            <p>
                                                <strong>@lang('transbaza_contractor_services.table_region')</strong>{{$service->region->name}}
                                            </p>
                                            <p>
                                                <strong>@lang('transbaza_contractor_services.table_city')</strong>{{$service->city->name}}
                                            </p>

                                            <div class="button">
                                                    <a href="{{route('my-services.show', $service->id)}}" class="btn-custom">@lang('transbaza_contractor_services.table_show')</a>
                                                    <a href="{{route('my-services.edit', $service->id)}}"
                                                       class="btn-custom edit-entity">@lang('transbaza_contractor_services.table_edit')</a>
                                                    <a href="#" class="btn-custom delete"
                                                       data-id="{{$service->id}}">@lang('transbaza_contractor_services.table_delete')</a>
                                            </div>
                                        </div>

                                    @endforeach

                                </div>
                            </div>
                        @else
                            <div class="not-found-wrap">
                                <h3>@lang('transbaza_contractor_services.no_services')</h3>
                                <div class="button">
                                    <a href="{{route('my-services.create')}}" class="btn-custom black">@lang('transbaza_contractor_services.add_service')</a>
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
            $(document).on('submit', '#serviceFilterForm', function (e) {
                e.preventDefault();
                var params = $('#serviceFilterForm').serialize();
                $.ajax({
                    url: 'my-services',
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
                e.preventDefault();
                var id = $(this).data('id');
                var tr = $(this).closest('tr')
                $.ajax({
                    url: '/my-services/' + id,
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