<h3>Список пользователей на дату {{\Carbon\Carbon::now()->format('d.m.Y')}}</h3>
<b>{{$curr_role->name ?? ''}} {{$type[0] === 'active' ? 'Actual' : $type[0]}} {{$col}} </b>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <tbody>
        <tr>
            <th></th>
            <th>#</th>
            <th>Email</th>
            <th>Телефон</th>
            <th>Последний визит</th>
            <th>Дней без визита</th>
            <th>Дата регистрации</th>
            <th>Дней в системе</th>
        </tr>
        @php
            $i = 0;
        @endphp
        @foreach($users as $user)
            <tr>
                <td>{{++$i}}</td>
                <td>{{$user->id}}</td>
                <td>{{$user->email}}</td>
                <td>{{$user->phone}}</td>
                <td>{{$user->last_activity->format('Y-m-d H:i')}}</td>
                <td>{{$user->last_activity->diffInDays(\Carbon\Carbon::now())}}</td>
                <td>{{$user->created_at->format('Y-m-d')}}</td>

                <td>{{$user->created_at->diffInDays(\Carbon\Carbon::now())}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($request->role === 'confirm')
    <h3>Не подтвердили Email</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <tbody>
            <tr>
                <th></th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Последний визит</th>
                <th>Дней без визита</th>
                <th>Дата регистрации</th>
                <th>Дней в системе</th>
            </tr>
            @php
                $i = 0;
            @endphp
            @foreach(\App\User::whereEmailConfirm(0)->get() as $user)
                <tr>
                    <td>{{++$i}}</td>
                    <td>{{$user->id}}</td>
                    <td>{{$user->email}}</td>
                    <td>{{$user->phone}}</td>
                    <td>{{$user->last_activity->format('Y-m-d H:i')}}</td>
                    <td>{{$user->last_activity->diffInDays(\Carbon\Carbon::now())}}</td>
                    <td>{{$user->created_at->format('Y-m-d')}}</td>

                    <td>{{$user->created_at->diffInDays(\Carbon\Carbon::now())}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
