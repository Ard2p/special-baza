<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Менеджер</th>
            <th>Старый статус</th>
            <th>Новый статус</th>
            <th>Дата изменения</th>
        </tr>
        </thead>
        <tbody>
        @foreach($lead->status_histories as $history)
            <tr>
                <td>{{$history->manager->id_with_email}}</td>
                <td>{{$history->old_status}}</td>
                <td>{{$history->new_status}}</td>
                <td>{{$history->created_at}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>