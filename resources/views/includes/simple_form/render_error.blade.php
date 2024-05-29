Введенный Email {{$users->count() === 2 ? 'и' : 'или'}} Телефон  принадлежит участнику системы TRANSBAZA. <br> Отправить заявку от лица участника: {!!  $users->count() === 1 ? '<br>#' . $users->first()->id : '' !!}
<form action="{{route('simple_form', $request->form_id)}}" style="display: flex; align-items: center;
        font-size: 12px;
    margin-top: 10px;
    justify-content: center;" id="__choose_users">
    @if($users->count() === 2)
        <label style="margin-right: 20px;">
            <input type="radio" name="_selected" style="height: 15px;" value="{{$users->first()->id}}" checked>
            <span class="swal2-label">#{{$users->first()->id}} <br> (найден по Email)</span>
        </label>
        <label>
            <input type="radio" name="_selected" style="height: 15px" value="{{$users->last()->id}}">
            <span
                    class="swal2-label">#{{$users->last()->id}} <br> (найден по Телефону)</span>
        </label>
    @else
        <input type="hidden" name="_selected" style="height: 15px;" value="{{$users->first()->id}}">
    @endif
    @foreach($request->except('_token') as $key => $value)
        <input type="hidden" name="{{$key}}" value="{{$value}}">
    @endforeach
</form>

{{--    <div class="form-group">
        <label class="radio-inline">
            <input type="radio" name="type" value="{{$users->first()->id}}" checked>#{{$users->first()->id}}
        </label>
        <label class="radio-inline">
            <input type="radio" name="type" value="{{$users->last()->id}}">
        </label>
    </div>--}}

<button type="button" aria-label="Да!" class="customSwalBtn" id="__send_by_user">Да! Отправить заявку!</button>
<br>
<p style="margin-top: 10px;
    font-size: 12px;
    font-weight: bold;" >Если вы участник системы, то вы можете авторизоваться в системе:</p>