<div class="panel">
    <div class="panel-body">

        <div class="col-md-6">
            <h3>Запросы к виджету</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Реферер</th>
                        <th>Успешно</th>
                        <th>Неудачно</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($widget_history as $item)
                        <tr>
                            <td>{{$item->referer}}</td>
                            <td>{{$item->success}}</td>
                            <td>{{$item->fail}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <h3>Заявки от виджета</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Адрес</th>
                        <th>Новый пользователь</th>
                        <th>Пользователь</th>
                        <th>Промо код</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($widget->widget_proposals as $proposal)
                        <tr>
                            <td>{{$proposal->id}}</td>
                            <td>{{$proposal->proposal->full_address}}</td>
                            <td>{{$proposal->new_user ? 'Да' : 'Нет'}}</td>
                            <td>{{$proposal->proposal->user->email}}</td>
                            <td>{{$proposal->promo}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>