<form class="panel panel-default" action="{{route('change_password')}}" method="POST">
    @csrf
    <div class="panel-body">
        <div class="form-elements">
            <div class="col-md-6">
                <div class="form-group form-element-text "><label class="control-label">
                        Изменить пароль

                        <span class="form-element-required">*</span></label> <input type="password" name="password" value=""
                                                                                    class="form-control">
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="user_id" value="{{$user_id}}" >
    <div class="form-buttons panel-footer">
        <div role="group" class="btn-group">
            <button type="submit" name="next_action" value="save_and_continue" class="btn btn-primary"><i
                        class="fa fa-check"></i> Изменить пароль
            </button>
           </div>
    </div>
</form>