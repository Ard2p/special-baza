@if($instance->moderated)
    <button type="button" class="btn btn-danger un_moderate" data-url="{{route('un_moderate', [$type, $instance->id])}}">Снять с публикации</button>
@else
    <button type="button" class="btn btn-info set_moderate" data-url="{{route('set_moderate', [$type, $instance->id])}}" >Опубликовать</button>
@endif