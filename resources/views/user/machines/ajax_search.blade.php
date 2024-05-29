@foreach($machines as $machine)
    <tr>
        <td>{{$machine->region->name}}</td>
        <td>{{$machine->city->name}}</td>
        <td>{{$machine->_type->name}}</td>
        <td>{{$machine->brand->name}}</td>
        <td>{{$machine->address}}</td>
        <td>{{$machine->user_id == Auth::user()->id ? 'Владелец' : 'Региональный представитель.'}}</td>
        <td>@if($machine->regional_representative_id == Auth::user()->id)
                #{{$machine->user->id}}  {{$machine->user->email}}
            @endif
        </td>
        <td>@if($machine->regional_representative_id == Auth::user()->id)
                <p class="phone">{{$machine->user->phone}}</p>
            @endif
        </td>
        <td>@if($machine->regional_representative_id == Auth::user()->id)
                {{$machine->user->getBalance('contractor') / 100}} руб.
            @endif
        </td>
        <td>
            @if($machine->user_id == Auth::user()->id)
                <a class="btn-machinaries"
                   href="/contractor/machinery/{{$machine->id}}/edit"><i
                            class="fas fa-file-signature"></i></a>
                <a class="btn-machinaries"
                   href="/contractor/machinery/{{$machine->id}}"><i
                            class="fas fa-info-circle"></i></a>
                <a class="btn-machinaries delete" data-id="{{$machine->id}}"><i
                            class="fas fa-trash"></i></a>
            @elseif($machine->regional_representative_id == Auth::user()->id)
                <a class="btn-machinaries"
                   href="/contractor/machinery/{{$machine->id}}"><i
                            class="fas fa-info-circle"></i></a>
            @endif
        </td>
    </tr>
@endforeach