<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Имя</th>
            <th>Должность</th>
            <th>Телефон</th>
            <th>Email</th>
        </tr>
        </thead>
        <tbody>
        @foreach($company->workers as $worker)
            <tr>
                <td>{{$worker->name}}</td>
                <td>{{$worker->position}}</td>
                <td>{{$worker->phone}}</td>
                <td>{{$worker->email}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>