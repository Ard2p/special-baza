@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">
        </div>
        <div class="row">


            <div class="col-md-12">
                <div id="machine_show">

                    <div class="machine-card">
                        <div class="row">
                            <div class="col-md-12">
                                <h1>{{$machine->name}}</h1>
                                <h2>{{$machine->_type->name}}</h2>
                            </div>
                            <div class="col-md-6">
                                <div class="image-wrap">
                                    <a class="thumbnail fancybox" rel="ligthbox" href=" /{{$machine->photo}}">
                                        <img alt="Фото техники"
                                             src="/{{$machine->photo}}" class="img-responsive"></a>
                                    <input id="profile-image-upload" class="hidden" type="file">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="list-params">
                                    <p>
                                        <strong>@lang('transbaza_auctions.brand')</strong>
                                        {{$machine->brand->name}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_auctions.region')</strong>
                                        {{$machine->region->name}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_auctions.address')</strong>
                                        {{$machine->address}}
                                    </p>
                                    <p>
                                        <strong>@lang('transbaza_auctions.start_sum')</strong>
                                        {{$auction->start_sum_format}} руб.
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_auctions.date_end')</strong>
                                        {{$auction->actual_date}}
                                    </p>

                                    @foreach($machine->optional_attributes as $attribute)
                                        <p>
                                            <span><b>{{$attribute->name}}</b> {{$attribute->pivot->value}} ({{$attribute->unit}})</span>
                                        </p>
                                    @endforeach

                                </div>
                            </div>

                            <div class="col-md-12">
                                <auction
                                        auction-id="{{$auction->id}}"
                                        locale="{{json_encode($locale)}}"
                                        here-route="{{route('in_auction', $auction->id)}}"
                                        add-bid-url="{{route('add_bid_auction', $auction->id)}}"
                                        diff-seconds="{{(now()->diffInSeconds($auction->actual_date, false)) < 0
                                        ? 0
                                        : now()->diffInSeconds($auction->actual_date, false)}}"
                                        expires-date="{{$auction->actual_date->format('c')}}">

                                </auction>

                            </div>
                            <div class="clearfix"></div>
                            <div class="hr-line"></div>
                            <div class="clearfix"></div>
                            <div class="col-md-4 harmony-accord">
                                <h3>@lang('transbaza_machine_edit.name_and_mark')</h3>
                                <div class="list-params">
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.name')</strong>
                                        {{$machine->name}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.manufacturer')</strong>
                                        {{$machine->manufacturer}}
                                    </p>
                                    @if($machine->regional_representative_id !== 0)
                                        <p>
                                            <strong>@lang('transbaza_machine_edit.regional_representative')</strong>
                                            # {{$machine->regional_representative_id}}
                                        </p>
                                    @endif
                                    @if($machine->promoter_id !== 0)
                                        <p>
                                            <strong>@lang('transbaza_machine_edit.promoter')</strong>
                                            # {{$machine->promoter_id}}
                                        </p>
                                    @endif
                                    @if($machine->sticker_promo_code)
                                        <p>
                                            <strong>@lang('transbaza_machine_edit.sticker_promo_code')</strong>
                                            {{$machine->sticker_promo_code}}
                                        </p>
                                    @endif
                                    @if($machine->sticker)
                                        <h2>@lang('transbaza_machine_edit.photo_with_sticker')</h2>
                                        <div class='col-12'>
                                            <a class="thumbnail fancybox" rel="ligthbox" href="/{{$machine->sticker}}">
                                                <img class="img-responsive" alt="" src="/{{$machine->sticker}}"/>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4 harmony-accord">
                                <h3>@lang('transbaza_machine_edit.certificate_conformity')</h3>
                                <div class="list-params">
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.certificate_conformity_number')</strong>
                                        {{$machine->certificate}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.certificate_date_conformity')</strong>
                                        {{$machine->certificate_date}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.issued_by')</strong>
                                        {{$machine->issued_by}}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4 harmony-accord">
                                <h3>Акт гостехосмотра</h3>
                                <div class="list-params">
                                    <p>
                                        <strong>Номер акта</strong>
                                        {{--Нет данных--}}
                                        {{$machine->act_number}}
                                    </p>

                                    <p>
                                        <strong>Дата акта</strong>
                                        {{--Нет данных--}}
                                        {{$machine->act_date}}
                                    </p>

                                    <p>
                                        <strong>Год выпуска</strong>
                                        {{--Нет данных--}}
                                        {{$machine->act_year}}
                                    </p>

                                </div>
                            </div>


                            <div class="clearfix"></div>
                            <div class="hr-line"></div>
                            <div class="clearfix"></div>
                            <div class="col-md-6 harmony-accord">
                                <h3>@lang('transbaza_machine_edit.details_psm')</h3>
                                <div class="list-params">
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.serial_number')</strong>
                                        {{--Нет данных--}}
                                        {{$machine->psm_manufacturer_number}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.engine')</strong>
                                        {{$machine->engine}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.transmission')</strong>
                                        {{$machine->transmission}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.leading_bridge')</strong>
                                        {{$machine->leading_bridge}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.color')</strong>
                                        {{$machine->colour}}
                                    </p>
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.engine_type')</strong>
                                        {{$machine->engine_type}}
                                    </p>
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.engine_power')</strong>
                                        {{$machine->engine_power}}
                                    </p>
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.construction_weight')</strong>
                                        {{$machine->construction_weight}}
                                    </p>
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.max_construction_speed')</strong>
                                        {{$machine->construction_speed}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.dimensions')</strong>
                                        {{--Нет данных--}}
                                        {{$machine->dimensions}}
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6 harmony-accord">
                                <h3>@lang('transbaza_machine_edit.registration_certificate')</h3>
                                <div class="list-params">
                                    <p>
                                        <strong>@lang('transbaza_machine_edit.issue_year')</strong>
                                        {{--Нет данных--}}
                                        {{$machine->year_release}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.owner')</strong>
                                        {{--Нет данных--}}
                                        {{$machine->owner}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.basis_for_witness')</strong>
                                        {{--Нет данных--}}
                                        {{$machine->basis_for_witness}}
                                    </p>

                                    <p>
                                        <strong>@lang('transbaza_machine_edit.witness_date')</strong>
                                        {{--Нет данных--}}
                                        {{$machine->witness_date}}
                                    </p>

                                </div>
                            </div>



                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="margin-wrap"></div>
                </div>
            </div>
        </div>
    </div>

@endsection