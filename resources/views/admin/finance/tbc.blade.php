<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <h4>История начисления TBC</h4>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#transactions" aria-controls="transactions" role="tab"
                                                      data-toggle="tab">
                    Все транзакции
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="transactions" class="tab-pane in active">
                <div class="container">
                    <form id="get_transaction_history" class="row" action="{{route('tbc_admin.index')}}">
                        <div class="col-md-6">
                            <div class="form-group form-element-select ">
                                <label for="billing_type" class="control-label">
                                    Выберите пользователя
                                    <span class="form-element-required">*</span>
                                </label>

                                <select class="form-control input-select" name="user_id">
                                    @foreach($users as $user)
                                        <option value="{{$user->id}}">{{$user->id_with_email}}</option>
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
                    <table class="table" id="tbc_table">
                        <thead>
                        <th>Пользователь</th>
                        <th>Дата создания</th>
                        <th>Сумма изменеия</th>
                        <th>Старый баланс</th>
                        <th>Новый баланс</th>
                        <th>Тип</th>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
