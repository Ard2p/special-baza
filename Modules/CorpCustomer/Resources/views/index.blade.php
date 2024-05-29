@extends('corpcustomer::layouts.global')

@section('content')


    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>Корпоративный заказчик</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>

                <div class="col-md-offset-1 col-md-8 search-wrap user-profile-wrap box-shadow-wrap">
                    <h3 class="title">Бренды</h3>
                    <div class="machinery-filter-wrap">
                        <div class="button">
                            <button type="button" onclick="location.href = '{{route('corp-brands.create')}}'"
                                    class="btn-custom">Добавить бренд
                            </button>
                        </div>
                    </div>
                    @if($brands->count())
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered"
                                   style="width:100%">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Наименование</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($brands as $item)
                                    <tr>
                                        <td>#{{$item->id}}</td>
                                        <td>{{$item->full_name}}</td>
                                        <td style="    display: inline-flex; width: 100%;">

                                            <a class="btn-machinaries"
                                               data-toggle="tooltip"
                                               title="Просмотр"
                                               href="{{route('corp-brands.show', $item->id)}}"><i
                                                        class="fas fa-eye"></i></a>
                                            <a class="btn-machinaries"
                                               data-toggle="tooltip"
                                               title="Изменить"
                                               href="{{route('corp-brands.edit', $item->id)}}"><i
                                                        class="fas fa-file-signature"></i></a>
                                            <form method="POST" action="{{route('corp-brands.destroy', $item->id)}}">
                                                @csrf
                                                @method('DELETE')
                                                <a class="btn-machinaries delete_widget" data-toggle="tooltip"
                                                   title="@lang('transbaza_widgets.delete')"
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
                            <h3>Бренды отсутствуют</h3>
                        </div>
                    @endif
                    <h3 class="title">Банки</h3>
                    <div class="machinery-filter-wrap">
                        <div class="button">
                            <button type="button" onclick="location.href = '{{route('corp-banks.create')}}'"
                                    class="btn-custom">Добавить банк
                            </button>
                        </div>
                    </div>
                    @if($banks->count())
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered"
                                   style="width:100%">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Наименование</th>
                                    <th>БИК</th>
                                    <th>Рассчетный счет</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($banks as $item)
                                    <tr>
                                        <td>#{{$item->id}}</td>
                                        <td>{{$item->name}}</td>
                                        <td>{{$item->bik}}</td>
                                        <td>{{$item->account}}</td>
                                        <td style="    display: inline-flex; width: 100%;">
                                            <a class="btn-machinaries"
                                               data-toggle="tooltip"
                                               title="Изменить"
                                               href="{{route('corp-banks.edit', $item->id)}}"><i
                                                        class="fas fa-file-signature"></i></a>
                                            <form method="POST" action="{{route('corp-banks.destroy', $item->id)}}">
                                                @csrf
                                                @method('DELETE')
                                                <a class="btn-machinaries" data-toggle="tooltip"
                                                   title="@lang('transbaza_widgets.delete')"
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
                            <h3>Банковские реквизиты отсутствуют</h3>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
