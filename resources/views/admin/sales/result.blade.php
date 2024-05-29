<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="panel">
        <div class="panel-body">

            <h3>Заявка на покупку #{{$sale->machine->name}}</h3>
            <div class="row">
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
                            <td>{{$sale->machine->user->id_with_email}}</td>
                            <td>{{$sale->machine->user->phone}}</td>
                            <td>{{$sale->machine->user->email}}</td>
                            <td>Владелец</td>
                        </tr>

                            <tr>
                                <td>{{$sale->user ? $sale->user->id_with_email : 'Нет'}}</td>
                                <td>{{$sale->phone}}</td>
                                <td>{{$sale->email}}</td>
                                <td>Покупатель</td>
                            </tr>


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