@extends('layouts.main')
@section('header')
    <meta name="description" content="@lang('transbaza_spectehnika.result_meta_description',
    [
    'name' => mb_strtolower($machine->_type->name),
    'machine_id' => $machine->id,
    'user_id' => $machine->user_id,
    'brand' => $machine->brand->name ?? '',
    'region' => $machine->region->name ?? '',
    'city' => $machine->city->name ?? ''
    ])">
    <meta name="keywords" content="@lang('transbaza_spectehnika.result_meta_keywords',
    ['name' => mb_strtolower($machine->_type->name),
    'machine_id' => $machine->id,
    'user_id' => $machine->user_id,
    'brand' => $machine->brand->name ?? '',
    'region' => $machine->region->name ?? '',
    'city' => $machine->city->name ?? ''
    ])">
    <title>@lang('transbaza_spectehnika.result_meta_title',
    [
    'name' => mb_strtolower($machine->_type->name),
    'machine_id' => $machine->id,
    'user_id' => $machine->user_id,
    'brand' => $machine->brand->name ?? '',
    'region' => $machine->region->name ?? '',
    'city' => $machine->city->name ?? ''
    ])</title>


@endsection
@section('content')

    <div class="container article-wrap bootstrap snippet">
        <ol class="breadcrumb">
            <li><a href="{{route('directory_main')}}">@lang('transbaza_spectehnika.index_bread_title')</a></li>
            <li>
                <a href="{{route('directory_main_category', $machine->_type->alias)}}">@lang('transbaza_spectehnika.rent_title', ['name' => $machine->_type->name_style])</a>
            </li>
            <li>
                <a href="{{route('directory_main_result', [$machine->_type->alias,  $machine->region->alias, $machine->city->alias])}}">@lang('transbaza_spectehnika.rent_in_city', ['city' => $machine->city->name, 'region' => $machine->city->region->name])</a></li>
            <li class="active">{{$machine->_type->name}} {{$machine->brand->name ?? ''}}</li>
        </ol>
        <div class="row">

            <h1 class="text-center">{{$machine->user_id}}.{{$machine->id}} @lang('transbaza_spectehnika.fast_form_title')</h1>
        </div>
        <form id="machine_show" action="{{route('make_rent')}}" itemscope itemtype="http://schema.org/Product">
            @include('user.machines.schema')
            <h2 class="text-center">{{$machine->_type->name}} {{$machine->brand->name ?? ''}}
                <br>{{$machine->city->name ?? ''}}, {{$machine->region->name ?? ''}}</h2>
            <div class="order-modal">
                @guest
                <div class="col-md-6 col-sm-6 col-xs-6">
                    <div class="form-item">
                        <label for="radio-input-yes-{{$machine->id}}" class="radio">
                          @lang('transbaza_spectehnika.new_customer')
                            <input type="radio" class="__radio"
                                   name="customer_type" value="new"
                                   id="radio-input-yes-{{$machine->id}}" checked>
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6" id="new_customer">
                    <div class="form-item">
                        <label  for="radio-input-no-{{$machine->id}}" class="radio">
                          @lang('transbaza_spectehnika.old_customer')
                            <input type="radio"
                                    value="old"
                                   >
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="machinery-filter-wrap" id="customer_data">
                    <div class="tree-cols-list">
                        <div class="col">
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="name" placeholder="@lang('transbaza_widgets.show_name')">
                                </label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="email" placeholder="@lang('transbaza_widgets.show_email')">
                                </label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="phone" class="phone" placeholder="@lang('transbaza_widgets.show_phone')">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                 @endguest
                <div class="machine-card" style="padding: 0px">
                    <div class="row">
                        <div class="col-md-6">
                     {{--       <div class="image-wrap">--}}

                                @include('user.machines.slider')

                              {{--  <a class="thumbnail fancybox" rel="ligthbox"
                                   href="/{{$machine->category_image}}">
                                    <img alt="{{$machine->_type->name}} {{$machine->brand->name ?? ''}} {{$machine->city->name ?? ''}}, {{$machine->region->name ?? ''}}"
                                         src="/{{$machine->category_image}}" style="width: auto"></a>
                                <input id="profile-image-upload" class="hidden" type="file">
                            </div>--}}
                        </div>

                        <div class="col-md-6">
                            <div class="list-params">
                                <p style="padding: 1px;">
                                                    <span><b>@lang('transbaza_machine_edit.cost_per_hour')</b> {{$machine->sum_hour_format}}
                                                        руб</span>

                                </p>
                                <p style="padding: 1px;">
                                                    <span><b>@lang('transbaza_machine_edit.cost_per_day')</b> {{$machine->sum_day_format}}
                                                        руб</span>

                                    {{--Нет данных--}}
                                </p>
                                @foreach($machine->optional_attributes as $attribute)
                                    <p style="padding: 1px;">
                                        <span><b>{{$attribute->name}}</b> {{$attribute->pivot->value}} ({{$attribute->unit}})</span>
                                    </p>
                                @endforeach

                                <p style="padding: 1px;">
                                    <span> <b>@lang('transbaza_machine_edit.work_day_duration') </b> {{$machine->change_hour}}</span>

                                </p>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="clearfix"></div>
                @csrf

                <div class="col-md-offset-2  col-md-8">
                    <div class="form-item">
                        <label>@lang('transbaza_widgets.show_region')</label>
                        <input class="promo_code" value="{{$machine->region->name ?? ''}}"
                               type="text" disabled>
                    </div>
                    <div class="form-item">
                        <label>@lang('transbaza_widgets.show_choose_city')</label>
                        <input class="promo_code" value="{{$machine->city->name ?? ''}}"
                               type="text" disabled>
                    </div>
                    <div class="form-item small">
                        <label for="price" class="required">@lang('transbaza_widgets.show_address')
                            <input type="text" name="address"
                                   placeholder="@lang('transbaza_widgets.show_need_address')">
                        </label>
                    </div>
                    @include('widget.bottom_form_piece')
                    <div class="form-item">
                        <div class="button">
                            <button type="submit" id="__tsb_submit" class="btn-custom">@lang('transbaza_widgets.show_send_proposal')
                            </button>
                        </div>
                    </div>
                </div>
                <div class="margin-wrap"></div>
            </div>
        </form>
    </div>
@guest
    <div class="modal modal-fade" id="login_form" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                                class="sr-only">Close</span></button>
                </div>
                <div class="modal-body">
                    <div class="auth">
                        @include('includes.auth_fields')
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal">@lang('transbaza_proposal_search.modal_close')</button>

                </div>
            </div>
        </div>
    </div>
    @endguest
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
               // var modal = $(this).closest('#machine_show')
                if (this.value == 'old') {
                    $('#customer_data').show();
                    $('#login_form').modal('hide');
                    //modal.find('input[name=name], input[name=phone]').hide();
                    //modal.find('input[name=email]').attr("placeholder", "Мой логин (email)");
                }
                else if (this.value == 'new') {

                    $('#customer_data').show();
                    $('#login_form').modal('hide');
                    //modal.find('input[name=name], input[name=phone]').show();
                    //modal.find('input[name=email]').attr("placeholder", "Email");
                }
            });
            $(document).on('click', '#new_customer',function () {

                $('#login_form').modal('show');
            })
            $().fancybox({
                selector : '.slick-slide:not(.slick-cloned)',
                hash     : false
            });

        </script>

    @endpush
    @include('scripts.machine.show')
@endsection