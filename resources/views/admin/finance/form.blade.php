<div>
    <h4>Баланс по счетам пользователя {{$user->id_with_email}}</h4>
    @foreach($user->roles_for_stats as $role)
       <p> <b> {{$role->name}}:</b> {{$user->getBalance($role->alias) / 100}}</p>
    @endforeach
</div>
<form class="panel panel-default panel panel-default change_balance" method="POST" action="{{route('fin_change_balance')}}">
   @csrf
    <div class="form-elements">
        <div class="panel-body">
            <div class="form-elements">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-elements">
                            <div class="form-group form-element-radio ">
                                <label for="type" class="control-label">
                                    Действие
                                </label>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="type" value="1"/>
                                        - уменьшение суммы (списание со счета)
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input checked
                                               type="radio" name="type"
                                               value="0"
                                        />
                                        - увеличение суммы (пополнение счета)
                                    </label>
                                </div>

                            </div>

                            <div class="form-group form-element-select ">
                                <label for="billing_type" class="control-label">
                                    Счет пользователя

                                    <span class="form-element-required">*</span>
                                </label>

                                <select class="form-control" name="billing_type">
                                    @foreach($user->roles_for_stats as $role)
                                    <option value="{{$role->alias}}">{{$role->name}}</option>
                                        @endforeach
                                </select>

                            </div>

                            <div class="form-group form-element-text ">
                                <label class="control-label">Сумма
                                </label>
                                <input type="number" name="sum" value="0" class="form-control"></div>
                            <div class="form-group form-element-text ">
                                <label class="control-label">Причина
                                </label>
                                <input type="text" name="reason" value="" class="form-control"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-elements">
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <hr/>
        <div>
            <div class="form-elements">
                <input type="hidden" id="user_id" name="user_id" value="{{$user->id}}"/>

            </div>
        </div>
    </div>
    <input type="hidden" name="_method" value="post"/>

    <div class="form-buttons panel-footer panel-footer">
        <div class="btn-group" role="group">
            <button type="submit" name="next_action" class="btn btn-primary" value="save_and_continue">
                <i class="fa fa-check"></i> Выполнить
            </button>

        </div>
    </div>
</form>