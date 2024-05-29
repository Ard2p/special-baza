<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <h4>Информация о пользователе {{$user->id_with_email}}</h4>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#transactions" aria-controls="transactions" role="tab"
                                                      data-toggle="tab">
                    Все транзакции
                </a></li>
            <li role="presentation"><a href="#balance" aria-controls="balance" role="tab" data-toggle="tab">

                    Выписка
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="transactions" class="tab-pane in active">
                <div class="container">
                    <form id="get_transaction_history" class="row" action="{{route('fin_transactions_history')}}">
                        <input type="hidden" name="user_id" value="{{$user->id}}">
                        <div class="col-md-6">
                            <div class="form-group form-element-select ">
                                <label for="billing_type" class="control-label">
                                    Счет пользователя

                                    <span class="form-element-required">*</span>
                                </label>

                                <select class="form-control" name="role">
                                    @foreach($user->roles_for_stats as $role)
                                        <option value="{{\App\User::getAccountRoleKey($role->alias === 'performer' ? 'contractor' : $role->alias )}}">{{$role->name}}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <button class="btn btn-primary">Показать</button>
                            </div>
                        </div>
                    </form>

                </div>
                <div class="table-responsive">
                    <table class="table" id="transactions_processed_table">
                        <thead>
                        <th>Тип транзакции</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действие</th>
                        <th>Примечание</th>
                        <th>Дата создания</th>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" id="balance" class="tab-pane">
                <div class="container">
                    <form id="get_balance_history" class="row" action="{{route('fin_balance_history')}}">
                        <input type="hidden" name="user_id" value="{{$user->id}}">
                        <div class="col-md-6">
                            <div class="form-group form-element-select ">
                                <label for="billing_type" class="control-label">
                                    Счет пользователя

                                    <span class="form-element-required">*</span>
                                </label>

                                <select class="form-control" name="billing_type">
                                    @foreach($user->roles_for_stats as $role)
                                        <option value="{{$role->alias === 'performer' ? 'contractor' : $role->alias }}">{{$role->name}}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <button class="btn btn-primary">Показать</button>
                            </div>
                        </div>
                    </form>

                </div>
                <div class="clearfix"></div>
                <b id="period_start"></b>
                    <div class="table-responsive">
                        <table class="table" id="balance_history_table">
                            <thead>
                            <th>Дата</th>
                            <th>Пополнение</th>
                            <th>Списание</th>
                            <th>Примечание</th>
                            <th>Остаток</th>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                <b id="period_end"></b>
            </div>
        </div>
    </div>
</div>
