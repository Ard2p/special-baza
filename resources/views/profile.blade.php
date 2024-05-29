@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">
            <div class="col-sm-10"><h1>Личный кабинет</h1></div>
        </div>
        <div class="row">
            <div class="col-md-3 col-xs-12"><!--left col-->

                @include('sections.info')

            </div><!--/col-3-->

            <div class="col-md-9 col-xs-12">
                <div id="tabs-panel">
                    <div class="button three-btns">
                        @performer <a href="{{route('order.index')}}">Заявки</a> @endPerformer
                        @customer <a href="/search">Поиск исполнителя</a> @endCustomer
                        <a href="#profile" class="btn-custom" data-toggle="tab">Профиль</a>
                        <a href="#in_transaction" class="btn-custom" data-toggle="tab">Пополнение счета</a>
                        <a href="#out_transaction" class="btn-custom" data-toggle="tab">Вывод денег со счета</a>
                        <a href="#history" class="btn-custom" data-toggle="tab">История баланса</a>
                    </div>
                    {{--<ul class="nav nav-tabs" id="myTab">--}}
                    {{--<li class="active"></li>--}}
                    {{--<li><</li>--}}
                    {{--<li><</li>--}}
                    {{--</ul>--}}
                    <hr>
                    <div class="tab-content">
                        <div class="tab-pane active" id="profile">
                            <div class="col-md-8 col-md-offset-2">
                                <form class="form-horizontal" role="form" id="userForm">
                                    @csrf
                                    <div class="form-item">
                                        <label>Email:</label>
                                        <input name="email" value="{{Auth::user()->email}}"
                                               type="text">
                                    </div>
                                    <div class="form-item">
                                        <label>Телефон:</label>
                                        <input name="phone" class="phone" value="{{Auth::user()->phone}}"
                                               type="text">
                                    </div>
                                  {{--  <div class="form-item">
                                        <label>Тип аккаунта:</label>
                                        <div class="custom-select-exp">
                                            <select name="account_type">
                                                <option value="">Выберите тип</option>
                                                <option value="entity" {{Auth::user()->account_type == 'entity' ? 'selected': ''}}>
                                                    Юридическое лицо
                                                </option>
                                                <option value="individual" {{Auth::user()->account_type == 'individual' ? 'selected': ''}}>
                                                    Физическое лицо
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-item">
                                        <label>Роль:</label>
                                        <label for="checked-input2" class="checkbox">
                                            Исполнитель
                                            <input type="checkbox" name="performer" value="1"
                                                   id="checked-input2" {{Auth::user()->checkRole('performer') ? 'checked' : ''}}>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>--}}
                                   {{-- <div class="form-item">
                                        <label for="checked-input" class="checkbox">
                                            Заказчик
                                            <input type="checkbox" name="customer" value="1"
                                                   id="checked-input" {{Auth::user()->checkRole('customer') ? 'checked' : ''}}>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>--}}
                                    <div class="form-group">
                                        <div class="col-md-offset-2 col-md-8">
                                            <div class="button two-btn">
                                                <input class="btn-custom" value="Сохранить" type="submit">
                                                <span></span>
                                                <input class="btn-custom" value="Отмена" type="reset">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <hr>
                                <form id="changePassword" class="form-horizontal">
                                    @csrf
                                    <div class="form-item">
                                        <label>Пароль:
                                            <input name="password" value="" type="password">
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label>Подтвердить пароль:
                                            <input name="password_confirmation" value="" type="password">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"></label>
                                        <div class="col-md-12">
                                            <div class="button two-btn">
                                                <input class="btn-custom" value="Изменить пароль" type="submit">
                                                <span></span>
                                                <input class="btn-custom" value="Отмена" type="reset">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane" id="in_transaction">
                            <hr>
                            @checkRequisite
                            <form id="in_transaction_form">
                                @csrf
                                <div class="col-md-offset-2 col-md-8">
                                    <div class="form-item">
                                        <label>Введите сумму для пополнения:</label>
                                        <input name="sum" value=""
                                               type="text">
                                    </div>
                                </div>
                                <input type="hidden" name="type" value="in">
                                <div class="col-md-offset-3 col-md-6">
                                    <div class="button">
                                        <button class="btn-custom" type="submit">Подтвердить</button>
                                    </div>
                                </div>

                            </form>
                            @else
                              <h4>Заполните пожалуйста реквизиты</h4>
                       @endCheckRequisite
                        </div>
                        <div class="tab-pane" id="out_transaction">
                            <hr>
                            @checkRequisite
                            <form id="out_transaction_form">
                                @csrf
                                <div class="col-md-offset-2 col-md-8">
                                    <div class="form-item">
                                        <label>Введите сумму для вывода:</label>
                                        <input name="sum" value=""
                                               type="text">
                                    </div>
                                </div>
                                <div class="col-md-offset-3 col-md-6">
                                    <div class="button">
                                        <button class="btn-custom" type="submit">Подтвердить</button>
                                    </div>
                                </div>
                                <input type="hidden" name="type" value="out">
                            </form>
                            @else
                                <h4>Заполните пожалуйста реквизиты</h4>
                                @endCheckRequisite
                        </div>
                        <div class="tab-pane" id="history">
                            <div id="tabs-panel">
                                <div class="button three-btns">
                                    <a href="#balance_history" class="btn-custom" data-toggle="tab">Все</a>
                                    <a href="#balance_wait" class="btn-custom" data-toggle="tab">Запросы</a>
                                </div>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="balance_history">
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>Старый баланс</th>
                                                    <th>Новый баланс</th>
                                                    <th>Сумма изменения</th>
                                                    <th>Причина</th>
                                                    <th>Дата</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($balances as $balance)
                                                    <tr>
                                                        <td>{{$balance->old_sum / 100}}</td>
                                                        <td>{{$balance->new_sum / 100}}</td>
                                                        <td>{{$balance->sum / 100}}</td>
                                                        <td>{{$balance->reason}}</td>
                                                        <td>{{$balance->created_at}}</td>

                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="tab-pane" id="balance_wait">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>Тип транзакциис</th>
                                                    <th>Сумма</th>
                                                    <th>Статус</th>
                                                    <th>Дата</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($balances_wait as $balance)
                                                    <tr>
                                                        <td>{{($balance->type) ? 'Вывод денег со счета' : 'Пополнение счета'}}</td>
                                                        <td>{{$balance->sum / 100}}</td>
                                                        <td>{{($balance->status_lng($balance->status))}}</td>
                                                        <td>{{$balance->created_at}}</td>

                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('scripts.profile.index')
@endsection