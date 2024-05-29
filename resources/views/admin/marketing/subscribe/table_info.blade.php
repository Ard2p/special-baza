<table class="table table-striped">
    <thead>
    <th>Пользователь</th>
    <th>Статус</th>
    <th>Время просмотра</th>
    </thead>
    <tbody>
    @foreach($list as $item)
        <tr>
            <td>{{$item->user->id_with_email}}</td>
            <td>@switch($item->confirm_status)
                    @case(0)
                    @break
                    @case(1)
                    Да
                    @break
                    @case(2)
                    Нет
                    @break
                @endswitch</td>
            <td>{{$item->watch_at}}</td>
        </tr>
    @endforeach
    </tbody>
</table>