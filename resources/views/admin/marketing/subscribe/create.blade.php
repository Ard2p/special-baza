<div class="content body" id="tables_content">
    <div class="panel">
        <div class="panel-body">
            <h3>Создать подписку</h3>
            <form class="row" method="POST" action="{{route('subscribe.store')}}">
                @csrf
                <div class="col-md-12">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Наименование подписки</label>
                            <input class="form-control" type="text" value="{{old('name')}}" name="name">
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Роли</label>
                            <select style="width:100%;" class="form-control input-select"
                                    data-placeholder="Выберите роли..." name="roles[]" multiple>
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}" >{{$role->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button class="btn btn-primary" type="submit">Сохранить</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <hr>

        </div>
    </div>

</div>