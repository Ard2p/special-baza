@foreach($machines as $machine)
    <div class="item">
        <p>
            <strong>Регион</strong>{{$machine->region->name}}
        </p>
        <p>
            <strong>Город</strong>{{$machine->city->name}}
        </p>
        <p>
            <strong>Категория техники</strong>{{$machine->_type->name}}
        </p>
        <p>
            <strong>Марка техники</strong>{{$machine->brand->name}}
        </p>
        <p>
            <strong>Адрес</strong>{{$machine->address}}
        </p>
        <p>
            <strong>Владелец</strong>{{$machine->address}} @if($machine->regional_representative_id == Auth::user()->id)
                #{{$machine->user->id}}  {{$machine->user->email}}
            @endif
        </p>
        <p >
            <strong>Телефон</strong>
            @if($machine->regional_representative_id == Auth::user()->id)
            <span class="phone">{{$machine->user->phone}}</span>
            @endif
        </p>
        <p>
            <strong>Баланс</strong>@if($machine->regional_representative_id == Auth::user()->id)
                {{$machine->user->getBalance('contractor') / 100}} руб.
            @endif
        </p>
        <div class="button">
            @if($machine->user_id == Auth::user()->id)
                <a href="/contractor/machinery/{{$machine->id}}" class="btn-custom">Просмотр</a>
                <a href="/contractor/machinery/{{$machine->id}}/edit"
                   class="btn-custom edit-entity">Редактировать</a>
                <a href="#" class="btn-custom delete"
                   data-id="{{$machine->id}}">Удалить</a>
            @elseif($machine->regional_representative_id == Auth::user()->id)
                <a href="/contractor/machinery/{{$machine->id}}" class="btn-custom">Просмотр</a>
            @endif
        </div>
    </div>

@endforeach
