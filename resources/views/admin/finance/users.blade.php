<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#all" aria-controls="all" role="tab" data-toggle="tab">
                    Все транзакции
                </a></li>
            <li role="presentation"><a href="#users" aria-controls="users" role="tab" data-toggle="tab">

                    Все пользователи
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="all" class="tab-pane in active">
                <div class="table-responsive">
                    <table class="table" id="transactions_table">
                        <thead>
                        <th>Пользователь</th>
                        <th>Тип транзакции</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действие</th>
                        <th>Примечание</th>
                        <th>Дата создания</th>
                        </thead>
                        <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{$transaction->user->email ?? ''}}</td>
                                <td>{{$transaction->type ? 'Вывод денег со счета' : 'Пополнение счета'}}</td>
                                <td>{{$transaction->sum_format}}</td>
                                <td>{{$transaction->status_name}}</td>
                                <td>
                                    @if(($transaction->status === $transaction->getStatus('wait') && $transaction->step === 0 && $transaction->card_payment === 0))
                                        <div>
                                            <button class="btn btn-primary accept" data-id="{{$transaction->id}}">
                                                Подтвердить
                                            </button>
                                            &nbsp;
                                            <button class="btn btn-danger refuse" data-id="{{$transaction->id}}">
                                                Отмена
                                            </button>
                                        </div>
                                    @else
                                        Нет доступных действий
                                    @endif
                                </td>
                                <td>{{($transaction->card_payment === 0)? 'Оплата по счету.': 'Оплата картой'}}</td>
                                <td>{{$transaction->created_at->format('d.m.Y H:i')}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="users" class="tab-pane">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>#</th>
                        <th>Email</th>
                        <th>Статус</th>
                        <th>Действие</th>
                        </thead>
                        <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{$user->id}}</td>
                                <td>{{$user->email}}</td>
                                <td>{!! $user->is_bolcked ? '<span class="label label-danger">Заблокирован</span>' : '<span class="label label-success">Активен</span>' !!}</td>
                                <td>
                                    <button class="btn btn-info change" data-url="{{route('get_form')}}" data-id="{{$user->id}}" type="button" >Изменить</button>
                                    <a class="btn btn-warning"  target="_blank" href="{{route('admin_user_transactions', $user->id)}}"><i class="fa fa-history"></i> </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal" tabindex="-1" role="dialog" id="info-modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Информация</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
