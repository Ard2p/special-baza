<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="panel">
        <div class="panel-body">

            <h3>Аукцион #{{$auction->machine->name}}</h3>
            <div class="row">
                <a href="/machineries/{{$auction->machine->id}}/edit" target="_blank">Карточка техники</a>
                <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{$auction->machine->user->id_with_email}}</td>
                        <td>{{$auction->machine->user->phone}}</td>
                        <td>{{$auction->machine->user->email}}</td>
                        <td>Владелец</td>
                    </tr>
                    @if($auction->last_offer && $auction->is_close)
                        <tr>
                            <td>{{$auction->last_offer->user->id_with_email}}</td>
                            <td>{{$auction->last_offer->user->phone}}</td>
                            <td>{{$auction->last_offer->user->email}}</td>
                            <td>Победитель</td>
                        </tr>
                    @endif

                    </tbody>
                </table>
                </div>
                <h4>История ставок</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>Ставка</th>
                            <th>Время</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($auction->offers as $offer)
                            <tr>
                                <td>#{{$offer->user_name}}</td>
                                <td>{{$offer->sum_format}}</td>
                                <td>{{$offer->created_at}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function (event) {

    });
</script>