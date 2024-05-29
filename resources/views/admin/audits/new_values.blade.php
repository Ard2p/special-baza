@if($item->new_values->isEmpty())
    Данные отсутствуют
@else
    <button type="button" data-toggle="collapse" data-target="#new{{{$item->id}}}"
            aria-expanded="false" aria-controls="new{{{$item->id}}}"
            class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
            data-display="new{{{$item->id}}}">
        <span class="fa fa-search"></span>
    </button>
    <div class="collapse" aria-expanded="false" id="new{{{$item->id}}}">

        <ul class="list-group">
            @foreach($item->new_values as $key => $value)
                <li class="list-group-item">{{$key}} : {{$value}}</li>
            @endforeach
        </ul>

    </div>
@endif