@foreach($templates as $template)
    <option value="{{$template->id}}">{{$template->name}}</option>
@endforeach
