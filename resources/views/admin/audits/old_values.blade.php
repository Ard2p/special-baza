@if($item->old_values->isEmpty())
    Данные отсутствуют
@else
    <button type="button" data-toggle="collapse" data-target="#stack{{{$item->id}}}"
            aria-expanded="false" aria-controls="stack{{{$item->id}}}"
            class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
            data-display="stack{{{$item->id}}}">
        <span class="fa fa-search"></span>
    </button>
    <div class="collapse" aria-expanded="false" id="stack{{{$item->id}}}">

        <ul class="list-group">
            @foreach($item->old_values as $key => $value)
                <li class="list-group-item">{{$key}} : {{$value}}</li>
            @endforeach
        </ul>

    </div>
@endif