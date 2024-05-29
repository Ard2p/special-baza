<table class="table table-striped">
    <thead>
    <tr>
        <th>Статистика</th>
        @foreach(Auth::user()->roles_for_stats as $role)
            <th>{{$role->name}}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Единиц техники</td>


        @foreach(Auth::user()->roles_for_stats as $role)
            <td>
                {{$role->alias === 'performer' ? Auth::user()->machines->count() : 0}}
            </td>
        @endforeach
    </tr>
    <tr>
        <td>Число заявок</td>
        @foreach(Auth::user()->roles_for_stats as $role)
            <td>
                {{$role->alias === 'customer'
                ? Auth::user()->proposals()->where('status', \App\Proposal::status('open'))->count()
                : ($role->alias === 'contractor' ?  Auth::user()->proposals()->where('status', \App\Proposal::status('open'))->whereHas('offers', function ($q){
                 $q->where('user_id', Auth::id())
                    ->where('is_win', 0);
                })->count() : 0) }}
            </td>
        @endforeach
    </tr>
    <tr>
        <td>
            Число заказов
        </td>
        @foreach(Auth::user()->roles_for_stats as $role)
            <td>
                {{$role->alias === 'customer' ? Auth::user()->proposals()->whereIn('status', [\App\Proposal::status('accept'), \App\Proposal::status('done')])->count()
                 : ($role->alias === 'contractor' ?  Auth::user()->proposals()->where('status', \App\Proposal::status('open'))->whereHas('offers', function ($q){
                     $q->where('user_id', Auth::id())
                        ->where('is_win', 1);
                    })->count() : 0) }}
            </td>
        @endforeach
    </tr>
    <tr>
        <td>
            Баланс
        </td>
        @foreach(Auth::user()->roles_for_stats as $role)
            <td>
                {{Auth::user()->getBalance(($role->alias === 'performer' ? 'contractor' : $role->alias)) / 100}}
            </td>
        @endforeach
    </tr>
    </tbody>
</table>