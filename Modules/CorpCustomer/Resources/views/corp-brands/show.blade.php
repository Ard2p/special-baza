@extends('corpcustomer::layouts.global')

@section('content')


    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>Бренд "{{$brand->full_name}}"</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>

                <div class="col-md-offset-1 col-md-8 search-wrap user-profile-wrap box-shadow-wrap">
                    <ol class="breadcrumb">
                        <li><a href="{{route('corp_index')}}">Главная</a></li>

                        <li class="active">{{$brand->full_name}}</li>
                    </ol>
                    <div class="machine-card">
                        <div class="list-params">
                            <p><strong>Полное наименование организации</strong>{{$brand->full_name}}</p>
                            <p><strong>Сокращенное наименование организации</strong>{{$brand->short_name}}</p>
                            <p><strong>Местонахождение</strong>{{$brand->address}}</p>
                            <p><strong>Почтовый адрес организации</strong>{{$brand->zip_code}}</p>
                            <p><strong>Контактный e-mail организации</strong>{{$brand->email}}</p>
                            <p><strong>Контактный телефон организации</strong>{{$brand->phone}}</p>
                            <p><strong>ИНН</strong>{{$brand->inn}}</p>
                            <p><strong>КПП</strong>{{$brand->kpp}}</p>
                            <p><strong>ОГРН</strong>{{$brand->ogrn}}</p>

                        </div>
                    </div>
                    @if($brand->banks->isNotEmpty())
                        <h3>Банковские реквизиты бренда</h3>
                        @foreach($brand->banks as $bank)
                            <b>{{$bank->name}}</b>
                            <div class="machine-card">
                                <div class="list-params">
                                    <p><strong>Рассчетный счет</strong>{{$bank->account}}</p>
                                    <p><strong>БИК</strong>{{$bank->bik}}</p>
                                    <p><strong>Адрес</strong>{{$bank->address}}</p>

                                </div>
                            </div>
                            <hr>
                        @endforeach
                    @else
                        <div class="not-found-wrap">
                            <h3>Банковские реквизиты отсутствуют</h3>
                        </div>
                    @endif
                    <h3 class="title">Компании бренда</h3>
                    <div class="machinery-filter-wrap">
                        <div class="button">
                            <button type="button"
                                    onclick="location.href = '{{route('corp-companies.create', ['brand_id' => $brand->id])}}'"
                                    class="btn-custom">Добавить компанию
                            </button>
                        </div>
                    </div>

                    @if($brand->companies->count())
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
                                @foreach($brand->companies as $item)
                                    <tr>
                                        <td>#{{$item->id}}</td>
                                        <td>{{$item->full_name}}</td>
                                        <td style="    display: inline-flex; width: 100%;">
                                            <a class="btn-machinaries"
                                               data-toggle="tooltip"
                                               title="Просмотр"
                                               href="{{route('corp-companies.show', $item->id)}}"><i
                                                        class="fas fa-eye"></i></a>
                                            <a class="btn-machinaries"
                                               data-toggle="tooltip"
                                               title="Изменить"
                                               href="{{route('corp-companies.edit', $item->id)}}"><i
                                                        class="fas fa-file-signature"></i></a>
                                            <form method="POST" action="{{route('corp-companies.destroy', $item->id)}}">
                                                @csrf
                                                @method('DELETE')
                                                <a class="btn-machinaries" data-toggle="tooltip"
                                                   title="Удалить компанию"
                                                ><i
                                                            class="fa fa-trash"></i></a>
                                            </form>
                                        </td>

                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
