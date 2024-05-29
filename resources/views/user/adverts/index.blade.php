@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>@lang('transbaza_adverts.my_adverts')</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9 col-xs-12 user-profile-wrap box-shadow-wrap">
                <div id="tabs-panel"{{-- class="button search-btns"--}}>
                    <ul class="nav nav-tabs" id="myTab">
                        <li style="width: 33%"><a href="#customer" class="{{--btn-custom black--}} active show"
                                                  data-toggle="tab">@lang('transbaza_adverts.tab_customer')</a></li>

                        <li style="width: 33%"><a href="#agent" {{--class="btn-custom"--}}
                            data-toggle="tab">@lang('transbaza_adverts.tab_agent')</a></li>

                        <li style="width: 33%"><a href="#contractor" {{--class="btn-custom"--}}
                            data-toggle="tab">@lang('transbaza_adverts.tab_contractor')</a></li>

                    </ul>

                    <div class="tab-content">

                        <div class="tab-pane active" id="customer">
                            <div class="clearfix"></div>

                                <h3 class="title">@lang('transbaza_adverts.adverts') </h3>
                                <div class="machinery-filter-wrap">
                                    <div class="button">
                                        <button type="button" onclick="location.href = '{{route('adverts.create')}}'"
                                                class="btn-custom">@lang('transbaza_adverts.add_advert')
                                        </button>
                                    </div>
                                </div>
                                @if($adverts->count())
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered"
                                               style="width:100%">
                                            <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>@lang('transbaza_adverts.table_name')</th>
                                                <th>@lang('transbaza_adverts.table_category')</th>
                                                <th>@lang('transbaza_adverts.table_status')</th>
                                                <th>@lang('transbaza_adverts.table_off')</th>
                                                <th>@lang('transbaza_adverts.table_date')</th>
                                                <th class="text-center"><i class="fa fa-cog"></i></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($adverts as $item)
                                                <tr {!! !$item->global_show ? 'style="background: #ddd;"' : '' !!}>
                                                    <td>#{{$item->id}}</td>
                                                    <td>{{$item->name}}</td>
                                                    <td>{{$item->category->name}}</td>
                                                    <td>{{$item->status_name}}</td>
                                                    <td class="text-center"><i class="fa fa-{!! $item->global_show ? 'minus' : 'check' !!}"></i></td>
                                                    <td>{{$item->created_at}}</td>
                                                    <td style="    display: inline-flex; width: 100%;">
                                                        <a class="btn-machinaries"
                                                           data-toggle="tooltip"
                                                           title="Редактировать"
                                                           href="{!! route('adverts.edit', $item->id) !!}"><i
                                                                    class="fas fa-pen"></i></a>
                                                        <a class="btn-machinaries"
                                                           data-toggle="tooltip"
                                                           title="Просмотр"
                                                           href="{!! $item->url !!}"><i
                                                                    class="fas fa-eye"></i></a>
                                                        <form method="POST"
                                                              action="{{route('adverts.destroy', $item->id)}}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a class="btn-machinaries delete_advert"
                                                               data-toggle="tooltip"
                                                               title="Удалить"
                                                            ><i
                                                                        class="fa fa-trash"></i></a>
                                                        </form>
                                                    </td>

                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                <div class="not-found-wrap">
                                    <h3>@lang('transbaza_adverts.no_adverts')</h3>
                                </div>
                            @endif

                        </div>
                        <div class="tab-pane" id="agent">
                            @if($agents->count())
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>@lang('transbaza_adverts.table_name')</th>
                                            <th>@lang('transbaza_adverts.table_category')</th>
                                            <th>@lang('transbaza_adverts.table_status')</th>
                                            <th>@lang('transbaza_adverts.table_date')</th>
                                            <th class="text-center"><i class="fa fa-cog"></i></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($agents as $item)
                                            <tr>
                                                <td>#{{$item->id}}</td>
                                                <td>{{$item->name}}</td>
                                                <td>{{$item->category->name}}</td>
                                                <td>{{$item->status_name}}</td>
                                                <td>{{$item->created_at}}</td>
                                                <td style="    display: inline-flex; width: 100%;">
                                                    <a class="btn-machinaries"
                                                       data-toggle="tooltip"
                                                       title="Просмотр"
                                                       href="{!! $item->url !!}"><i
                                                                class="fas fa-file-signature"></i></a>
                                                 {{--   <form method="POST"
                                                          action="{{route('adverts.destroy', $item->id)}}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a class="btn-machinaries delete_advert"
                                                           data-toggle="tooltip"
                                                           title="Удалить"
                                                        ><i
                                                                    class="fa fa-trash"></i></a>
                                                    </form>--}}
                                                </td>

                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="not-found-wrap">
                                    <h3>@lang('transbaza_adverts.no_adverts')</h3>
                                </div>
                            @endif
                        </div>
                        <div class="tab-pane" id="contractor">
                            @if($im_contractor->count())
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>@lang('transbaza_adverts.table_name')</th>
                                            <th>@lang('transbaza_adverts.table_category')</th>
                                            <th>@lang('transbaza_adverts.table_status')</th>
                                            <th>@lang('transbaza_adverts.table_date')</th>
                                            <th class="text-center"><i class="fa fa-cog"></i></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($im_contractor as $item)
                                            <tr>
                                                <td>#{{$item->id}}</td>
                                                <td>{{$item->name}}</td>
                                                <td>{{$item->category->name}}</td>
                                                <td>{{$item->status_name}}</td>
                                                <td>{{$item->created_at}}</td>
                                                <td style="    display: inline-flex; width: 100%;">
                                                    <a class="btn-machinaries"
                                                       data-toggle="tooltip"
                                                       title="Просмотр"
                                                       href="{!! $item->url !!}"><i
                                                                class="fas fa-file-signature"></i></a>
                                                    {{--   <form method="POST"
                                                             action="{{route('adverts.destroy', $item->id)}}">
                                                           @csrf
                                                           @method('DELETE')
                                                           <a class="btn-machinaries delete_advert"
                                                              data-toggle="tooltip"
                                                              title="Удалить"
                                                           ><i
                                                                       class="fa fa-trash"></i></a>
                                                       </form>--}}
                                                </td>

                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="not-found-wrap">
                                    <h3>@lang('transbaza_adverts.no_adverts')</h3>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection