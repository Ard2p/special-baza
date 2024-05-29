<h2>@lang('transbaza_analysis.analysis')</h2>
<form id="analyse">
    <div class="machinery-filter-wrap">

        <div class="tree-cols-list">
            <div class="col" id="cat">
                <helper-select-input :data="{{\App\Machines\Type::all()->toJson()}}"
                                     :column-name="{{json_encode(trans('transbaza_analysis.choose_type'))}}"
                                     :place-holder="{{json_encode(trans('transbaza_analysis.choose_type'))}}"
                                     :col-name="{{json_encode('type')}}"
                                     :show-column-name="1"
                                     :initial="{{json_encode($initial_type ?? '')}}"></helper-select-input>
            </div>
            <div class="col">
                <helper-select-input :data="{{\App\Support\Region::all()->toJson()}}"
                                     :column-name="{{json_encode(trans('transbaza_analysis.choose_region'))}}"
                                     :place-holder="{{json_encode(trans('transbaza_analysis.choose_region'))}}"
                                     :col-name="{{json_encode('region')}}"
                                     :required="0"
                                     :initial="{{json_encode($initial_region ?? '')}}"
                                     :show-column-name="1"
                                     :hide-city="1">
                </helper-select-input>

            </div>
            <div class="col">
                <helper-select-input :data="{{ json_encode([])}}"
                                     :column-name="{{json_encode(trans('transbaza_analysis.choose_city'))}}"
                                     :place-holder="{{json_encode(trans('transbaza_analysis.choose_city'))}}"
                                     :col-name="{{json_encode('city_id')}}"
                                     :required="0"
                                     :initial="{{json_encode($checked_city_source ?? '')}}"
                                     :show-column-name="1"
                                     :hide-city="1">
                </helper-select-input>
            </div>

        </div>

        <div class="button">
            <button type="button" id="show_analyse_stats" class="btn-custom">@lang('transbaza_analysis.btn_show')</button>
        </div>
    </div>
</form>
<div id="table_col"></div>
<h2>@lang('transbaza_analysis.analysis_by_region')</h2>
<form id="analyse_region">
    <div class="machinery-filter-wrap">

        <div class="tree-cols-list">
            <div class="col" id="cat_2">
                <helper-select-input :data="{{\App\Machines\Type::all()->toJson()}}"
                                     :column-name="{{json_encode(trans('transbaza_analysis.choose_type'))}}"
                                     :place-holder="{{json_encode(trans('transbaza_analysis.choose_type'))}}"
                                     :col-name="{{json_encode('type2')}}"
                                     :show-column-name="1"
                                     :initial="{{json_encode($initial_type ?? '')}}"></helper-select-input>
            </div>
        </div>
        <div class="button">
            <button type="button" id="show_analyse_region" class="btn-custom">@lang('transbaza_analysis.btn_show')</button>
        </div>
    </div>
</form>
<div id="table_col2"></div>
@if(Auth::check())
    <h2>@lang('transbaza_analysis.my_stats')</h2>

    <div class="machinery-filter-wrap">
        <div class="button">
            <button type="button" id="show_my_stats" class="btn-custom">@lang('transbaza_analysis.btn_show')</button>
        </div>
    </div>
    <div class="table-responsive hidden" id="my_stats">
        @include('stat.table_user_stats')
    </div>
    @if(Auth::user()->isSuperAdmin())
        <h2>Пользователей на {{\Carbon\Carbon::now()->format('d.m.Y')}}</h2>
        <div class="table-responsive">
            @include('stat.table_user_count')
        </div>
    @endif
@endif
<h2>@lang('transbaza_analysis.tb_regions')</h2>
<form id="total_stats" action="{{route('get_total_stats')}}">
    @csrf
    <div class="col-md-6">
        <div class="col-md-3 col-sm-3 col-xs-3">
            <div class="form-item">
                <label for="radio-input-yes" class="radio">
                    n(m)
                    <input type="radio" name="show_type" value="n_m" id="radio-input-yes" checked>
                    <span class="checkmark"></span>
                </label>
            </div>
        </div>
        <div class="col-md-3 col-sm-3 col-xs-3">
            <div class="form-item">
                <label for="radio-input-no" class="radio">
                    m(n)
                    <input type="radio" name="show_type" value="m_n" id="radio-input-no">
                    <span class="checkmark"></span>
                </label>
            </div>
        </div>
        <div class="col-md-3 col-sm-3 col-xs-3">
            <div class="form-item">
                <label for="radio-input-m" class="radio">
                    m
                    <input type="radio" name="show_type" value="m" id="radio-input-m">
                    <span class="checkmark"></span>
                </label>
            </div>
        </div>
        <div class="col-md-3 col-sm-3 col-xs-3">
            <div class="form-item">
                <label for="radio-input-n" class="radio">
                    n
                    <input type="radio" name="show_type" value="n" id="radio-input-n">
                    <span class="checkmark"></span>
                </label>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="col-md-6">
        <b>@lang('transbaza_analysis.vehicles_count')</b><br><b> @lang('transbaza_analysis.contractors_count')</b>
    </div>
    <div class="machinery-filter-wrap">

        <div class="tree-cols-list">
            <div class="col">
                <helper-select-input :data="{{\App\Machines\Type::whereHas('machines')->get()->toJson()}}"
                                     :column-name="{{json_encode(trans('transbaza_analysis.choose_type'))}}"
                                     :place-holder="{{json_encode(trans('transbaza_analysis.choose_type'))}}"
                                     :col-name="{{json_encode('category')}}"
                                     :show-column-name="1"
                                     :initial="{{json_encode($initial_type ?? '')}}"></helper-select-input>
            </div>
            <div class="col">
                <helper-select-input :data="{{\App\Support\Region::whereHas('machines')->get()->toJson()}}"
                                     :column-name="{{json_encode(trans('transbaza_analysis.choose_region'))}}"
                                     :place-holder="{{json_encode(trans('transbaza_analysis.choose_region'))}}"
                                     :col-name="{{json_encode('region_id')}}"
                                     :required="0"
                                     :cities="{{json_encode(['url' => 'api/get-cities'])}}"

                                     :initial="{{json_encode($initial_region ?? '')}}"
                                     :show-column-name="1"
                                     :hide-city="1">
                </helper-select-input>
            </div>
            <div class="col">
                <helper-select-input :data="{{ json_encode([])}}"
                                     :column-name="{{json_encode(trans('transbaza_analysis.choose_city'))}}"
                                     :place-holder="{{json_encode(trans('transbaza_analysis.choose_city'))}}"
                                     :col-name="{{json_encode('city')}}"
                                     :required="0"
                                     :regions="{{json_encode(['url' => 'api/get-regions'])}}"
                                     :initial="{{json_encode($checked_city_source ?? '')}}"
                                     :show-column-name="1"
                                     :hide-city="1"></helper-select-input>
            </div>

        </div>
        <div class="button">
            <button type="submit" class="btn-custom">@lang('transbaza_analysis.btn_show')</button>
        </div>

    </div>
</form>
<div id="__table_total"></div>
<div class="modal modal-fade" id="info-modal" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                            class="sr-only">Close</span></button>
                <h4 class="modal-title">
                    Информация
                </h4>
            </div>
            <div class="modal-body">

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>

            </div>
        </div>
    </div>
</div>
@push('after-scripts')
    <script>
        var _id = 0;
        var _id_analyse = 0;
        var _type = 0;
        var __id;
        $(document).on('click', '#show_analyse_stats', function () {
            getStats()
        })
        $(document).on('click', '#cat .autocomplete--clear', function () {
            _id = 0;
        })
        $(document).on('click', '#show_analyse_region', function () {
            $.ajax({
                url: '{{route('get_region_stats')}}',
                type: 'GET',
                data: {category: _id_analyse},
                success: function (r) {

                    $('#table_col2').html('').html(r)
                },
                error: function () {

                }
            })
        })
        $(document).on('type2', function (e, name, value, id) {
            _id_analyse = id;

        })
        $(document).on('region city_id type', function (e, name, value, id) {
            console.log('name');
            if (name === 'type') {
                _id = id;
                if (_type == 0) {
                    return;
                }

            } else {
                _type = name;
                __id = id;
            }
        })
        $(document).on('click', '.more-info', function () {
            var category = $(this).data('category');
            var city = $(this).data('city');
            var region = $(this).data('region');
            var type = $(this).data('type');
            $('#info-modal').modal('show')
            $('#info-modal .modal-body').html('')
            $.ajax({
                url: '{{route('more_info')}}',
                type: 'GET',
                data: {
                    category_id: category,
                    city_id: city,
                    type_id: type,
                    region_id: region,
                },
                success: function (response) {
                    $('#info-modal .modal-body').html(response)
                },
                error: function (r) {
                    showErrors(r);
                }
            })
        })
        $(document).on('click', '.user_info', function () {
            var role = $(this).parent().data('role') || $(this).data('role');
            var type = $(this).data('type');
            $('#info-modal').modal('show')
            $('#info-modal .modal-body').html('')
            $.ajax({
                url: '{{route('more_user_info')}}',
                type: 'GET',
                data: {
                    role: role,
                    type: type,
                },
                success: function (response) {
                    $('#info-modal .modal-body').html(response)
                },
                error: function (r) {
                    showErrors(r);
                }
            })
        })
        $(document).on('submit', '#total_stats', function (e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $('#__table_total').html(response)
                },
                error: function (r) {
                    showErrors(r);
                }
            })
        })

        $(document).on('click', '#show_my_stats', function () {
            $('#my_stats').toggleClass('hidden')
        })

        function getStats() {
            $.ajax({
                url: '{{route('get_stats')}}',
                type: 'GET',
                data: {type: _type, id: __id, category: _id},
                success: function (r) {

                    $('#table_col').html('').html(r)
                },
                error: function () {

                }
            })
        }
    </script>
    <style>
        .more-info, .user_info:hover {
            cursor: pointer;
            background: #8aaeff;
        }

        .rotate_table th.rotate {
            /* Something you can count on */
            height: 200px;
            white-space: nowrap;
        }

        .rotate_table th.rotate > div {
            transform: translate(0px, 0px) rotate(270deg);
            width: 30px;
        }

        #table_total,#users_stat  th.rotate > div > span {
            border-bottom: 1px solid #ccc;
            padding: 5px 10px;
        }
    </style>
@endpush
