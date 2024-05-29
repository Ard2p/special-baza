<div class="form-group form-element-dependentselect ">
    <label class="control-label">
        Мой Региональный представитель
    </label>
    @if($user->is_regional_representative === 0)
        <div>
            <input type="hidden" id="current_user" value="{{$user->id}}">
            <input type="hidden" id="patch_url" value="{{route('regional.update', $user->id)}}">
            <select id="_regional" size="2" data-select-type="single"
                    class="form-control input-select">
                <option value="0" {{$user->regional_representative_id === 0 ? 'selected' : ''}}>Не выбран</option>
                @foreach($users as $item)
                    <option value="{{$item->id}}" {{$user->regional_representative_id === $item->id ? 'selected' : ''}}>{{$item->id_with_email}}</option>
                @endforeach
            </select>
        </div>
        <hr>
        <button class="btn btn-primary" type="button" id="change_rp">Изменить РП</button>
    @else
        <p>Пользователь является РП</p>
        @if($user->commission)
            <hr>
            <div class="form-group form-element-checkbox ">
                <div class="checkbox"><label><input id="individual_commission" type="checkbox" {{$user->commission->enable  ? 'checked' : ''}}>

                        Использовать индивидуальную комиссию.
                    </label></div>
            </div>
            <div class="form-group form-element-text "><label  class="control-label">
                    Размер комиссии (%)

                </label> <input type="number" min="0" step="1" max="100" id="individual_commission_value" value="{{$user->commission->percent_format}}" class="form-control" pattern="[0-9]{2}"></div>
            <hr>
            <button class="btn btn-primary" data-url="{{route('set_commission', $user->id)}}" type="button" id="change_percent">Применить комиссию</button>
        @endif
    @endif

</div>
