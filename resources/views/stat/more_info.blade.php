@php
    $city = \App\City::find($request->city_id);
@endphp
@if($request->type_id === 'n_m' || $request->type_id === 'm_n' || $request->type_id ==='n')
    <h3>Техника</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <tbody>
            <tr>
                 <th>#</th>
                <th>Категория</th>
                <th>Регион</th>
                <th>Город</th>
                <th>Гос. номер</th>
                <th>#ID</th>
                <th>Email</th>
                <th>Телефон</th>
            </tr>
            @php
                $i = 0;
            @endphp
            @foreach($machines as $machine)
                <tr>
                    <td>{{++$i}}</td>
                    <td>{{$machine->_type->name}}</td>
                    <td>{{$machine->region->name}}</td>
                    <td>{{$machine->city->name}}</td>
                    <td>{{$machine->number}}</td>
                    <td>#{{$machine->user->id}}</td>
                    <td>{{$machine->user->email}}</td>
                    <td><p class="phone">{{$machine->user->phone}}</p></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@if($request->type_id === 'n_m' || $request->type_id === 'm_n' || $request->type_id ==='m')
    <h3>Исполнители</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <tbody>
            <tr>
                <th>#</th>
                <th>Категория</th>
                <th>Регион</th>
                <th>Город</th>
                <th>#ID</th>
                <th>Email</th>
                <th>Телефон</th>
            </tr>
              @php
              $i = 0;
              @endphp
            @foreach($users as $user)
                <tr>
                    <td>{{++$i}}</td>
                    <td>{{\App\Machines\Type::find($request->category_id)->name ?? ''}}</td>
                    <td>{{$city->region->name ?? ''}}</td>
                    <td>{{$city->name ?? ''}}</td>
                    <td>{{$user->id}}</td>
                    <td>{{$user->email}}</td>
                    <td> <p class="phone">{{$user->phone}}</p></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
