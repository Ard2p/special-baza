@extends('layouts.main')
@section('content')

    <div class="search-wrap">
        <div class="button search-btns">
            @performer <a href="#" class="btn-custom black" data-id="1">Мои заявки</a> @endPerformer
            @customer <a href="#" class="btn-custom black" data-id="1">Мои заявки</a> @endCustomer

            @performer <a href="#" class="btn-custom" data-id="2">Приглашения</a> @endPerformer
            @customer <a href="#" class="btn-custom" data-id="3">Мои заказы</a> @endCustomer

            @performer <a href="#" class="btn-custom" data-id="4">Карточки ТС</a> @endPerformer
        </div>

        @performer
        <div id="tab1" class="active tab-list">
            <div class="title-wrap">
                <h1 class="title">Мои заявки</h1>
            </div>
            <div class="detail-search">
                <table class="table table-striped table-bordered adaptive-table"
                       style="width:100%">
                    <thead>
                    <tr>
                        <th>Регион</th>
                        <th>Адрес</th>
                        <th>Тип техники</th>
                        <th>Статус</th>
                        <th>Бюджет</th>
                        <th>Дата заказа</th>
                        <th>Коментарий</th>
                        <th style="text-align: center"><em class="fa fa-cog"></em></th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($offers_proposals as $proposal)
                        <tr>
                            <td data-label="Регион: ">{{$proposal->region->name}}</td>
                            <td data-label="Адрес: ">{{$proposal->address}}</td>
                            <td data-label="Тип техники: ">{{$proposal->_type->name}}</td>
                            <td data-label="Статус: ">{{$proposal->status_lng($proposal->status)}}</td>
                            <td data-label="Бюджет (руб): ">{{$proposal->sum  / 100}}</td>
                            <td data-label="Дата заказа: ">{{$proposal->date->format('d.m.Y')}}</td>
                            <td data-label="Коментарий: ">{{$proposal->comment}}</td>
                            <td><a class="" href="/proposals/{{$proposal->id}}">Просмотр</a></td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>

                <div class="list-proposals">
                    <h1>Список заявок</h1>
                    <div class="proposal-items">
                        @foreach($offers_proposals as $proposal)
                            <div class="item">
                                <p class="region">
                                    <i></i>
                                    {{$proposal->region->name}}
                                </p>
                                <p class="type">
                                    <i></i>
                                    {{$proposal->_type->name}}
                                </p>
                                <p class="budget">
                                    <i></i>
                                    {{$proposal->sum  / 100}} руб
                                </p>
                                <p class="date">
                                    <i></i>
                                    {{$proposal->date->format('d.m.Y')}}
                                </p>

                                <div class="button">
                                    <a href="/proposals/{{$proposal->id}}" class="btn-custom">Просмотр</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
               {{-- @foreach($offers_proposals as $proposal)
                    <div class="card b-1 hover-shadow mb-20">
                        <div class="media card-body">
                            <div class="float-right">
                                <p class="fs-14 text-fade mb-12" data-toggle="tooltip" title="{{$proposal->address}}"><i
                                            class="fa fa-map-marker pr-1"></i> {{$proposal->region->name}}
                                </p>
                                <p class="fs-14 text-fade mb-12"><i
                                            class="pr-1"></i>Статус: {{$proposal->status_lng($proposal->status)}}.
                                </p>
                                <span class="text-fade"><i
                                            class="fa fa-money pr-1"></i>{{$proposal->sum  / 100}}
                                    руб.</span>
                            </div>
                            <div class="media-body">
                                <div class="mb-2">
                                    <span class="fs-20 pr-16">{{$proposal->_type->name}}</span>
                                </div>
                                <small class="fs-16 fw-300 ls-1">{{$proposal->comment}}</small>
                                <div class="pr-12">
                                    <b></b>
                                </div>

                            </div>

                        </div>
                        <div class="card-footer flexbox align-items-center">

                            <div>
                                <strong>Заказ на:</strong>
                                <span>{{\Carbon\Carbon::parse($proposal->date)->format('d.m.Y')}}</span>
                            </div>
                            <div class="card-hover-show">
                                <a class="btn btn-xs fs-10 btn-bold btn-info" href="/proposals/{{$proposal->id}}">Просмотр</a>
                                @if(Auth::user()->id == $proposal->user_id)  <a class="btn btn-xs fs-10 btn-bold btn-warning trashProposal" data-id="{{$proposal->id}}">Удалить</a> @endif
                            </div>
                        </div>
                    </div>
                @endforeach--}}
            </div>
        </div>
        <div id="tab2" class="tab-list">
            <div class="title-wrap">
                <h1 class="title">Приглашения</h1>
            </div>
            <div class="detail-search">
               <p> {{$invites_proposals->isEmpty() ?
                'В настоящий момент вам не приходило ни одного Предложения.
                Подождите, скоро в системе появятся Заявки соответствующие вашей технике.
                Обратите внимание, что Заявки поступают только на конкретные типы техники
                в конкретном регионе и со статусом “Доступно” в календаре занятости техники' : ''}} </p>
                <table class="table table-striped table-bordered adaptive-table"
                       style="width:100%">
                    <thead>
                    <tr>
                        <th>Регион</th>
                        <th>Адрес</th>
                        <th>Категория техники</th>
                        <th>Статус</th>
                        <th>Бюджет</th>
                        <th>Дата заказа</th>
                        <th>Коментарий</th>
                        <th style="text-align: center"><em class="fa fa-cog"></em></th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($invites_proposals as $proposal)
                        <tr>
                            <td data-label="Регион: ">{{$proposal->region->name}}</td>
                            <td data-label="Адрес: ">{{$proposal->address}}</td>
                            <td data-label="Категория техники: ">{{$proposal->_type->name}}</td>
                            <td data-label="Статус: ">{{$proposal->status_lng($proposal->status)}}</td>
                            <td data-label="Бюджет (руб): ">{{$proposal->sum  / 100}}</td>
                            <td data-label="Дата заказа: ">{{$proposal->date->format('d.m.Y')}}</td>
                            <td data-label="Коментарий: ">{{$proposal->comment}}</td>
                            <td><a class="" href="/proposals/{{$proposal->id}}">Просмотр</a></td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>

                <div class="list-proposals">
                    <h1>Список заявок</h1>
                    <div class="proposal-items">
                        @foreach($invites_proposals as $proposal)
                            <div class="item">
                                <p class="region">
                                    <i></i>
                                    {{$proposal->region->name}}
                                </p>
                                <p class="type">
                                    <i></i>
                                    {{$proposal->_type->name}}
                                </p>
                                <p class="budget">
                                    <i></i>
                                    {{$proposal->sum  / 100}} руб
                                </p>
                                <p class="date">
                                    <i></i>
                                    {{$proposal->date->format('d.m.Y')}}
                                </p>

                                <div class="button">
                                    <a href="/proposals/{{$proposal->id}}" class="btn-custom">Просмотр</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endPerformer
        @customer
        <div id="tab3" class="tab-list">
            <div class="title-wrap">
                <h1 class="title">Мои заказы</h1>
            </div>
            <div class="detail-search">
                <table class="table table-striped table-bordered adaptive-table"
                       style="width:100%">
                    <thead>
                    <tr>
                        <th>Регион</th>
                        <th>Адрес</th>
                        <th>Категория техники</th>
                        <th>Статус</th>
                        <th>Бюджет</th>
                        <th>Дата заказа</th>
                        <th>Коментарий</th>
                        <th style="text-align: center"><em class="fa fa-cog"></em></th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($proposals as $proposal)
                        <tr>
                            <td data-label="Регион: ">{{$proposal->region->name}}</td>
                            <td data-label="Адрес: ">{{$proposal->address}}</td>
                            <td data-label="Категория техники: ">{{$proposal->_type->name}}</td>
                            <td data-label="Статус: ">{{$proposal->status_lng($proposal->status)}}</td>
                            <td data-label="Бюджет (руб): ">{{$proposal->sum  / 100}}</td>
                            <td data-label="Дата заказа: ">{{$proposal->date->format('d.m.Y')}}</td>
                            <td data-label="Коментарий: ">{{$proposal->comment}}</td>
                            <td><a class="" href="/proposals/{{$proposal->id}}">Просмотр</a></td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>

                <div class="list-proposals">
                    <h1>Список заявок</h1>
                    <div class="proposal-items">
                        @foreach($proposals as $proposal)
                            <div class="item">
                                <p class="region">
                                    <i></i>
                                    {{$proposal->region->name}}
                                </p>
                                <p class="type">
                                    <i></i>
                                    {{$proposal->_type->name}}
                                </p>
                                <p class="budget">
                                    <i></i>
                                    {{$proposal->sum  / 100}} руб
                                </p>
                                <p class="date">
                                    <i></i>
                                    {{$proposal->date->format('d.m.Y')}}
                                </p>

                                <div class="button">
                                    <a href="/proposals/{{$proposal->id}}" class="btn-custom">Просмотр</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endCustomer
        @performer
        <div id="tab4" class="tab-list">
            <div class="title-wrap">
                <h1 class="title">Карточки ТС</h1>
            </div>
            <div class="detail-search">

                <div class="button">
                    <button id="addMachine" class="btn-custom black">Добавить технику</button>
                </div>
                <hr>
                <div id="machine_form" style="display: none">
                    @include('machines.create')
                </div>
                <div class="table-responsive">
                    <table id="machines_table" class="table table-striped table-bordered"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>Наименование</th>
                            <th>Марка</th>
                            <th>Тип техники</th>
                            <th>Регион</th>
                            <th>Адрес</th>
                            <th style="text-align: center"><em class="fa fa-cog"></em></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>

                        </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        @endPerformer
    </div>

    @include('scripts.office')
@endsection