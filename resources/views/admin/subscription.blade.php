<div class="panel-body">
    <div class="form-elements">
        <div class="col-md-6">

            <div class="form-group form-elements">
                <label class="control-label">
                    Email для уведомлений</label>
                <input type="text" name="subscription_email"
                       value="{{$options->where('key', 'subscription_email')->first()->value }}" class="form-control">
            </div>
            <b>Подписки на уведомления</b>
            <div class="form-group form-element-checkbox ">
                <div class="checkbox"><label>
                        <input id="subscription_users" name="subscription_users"
                                                    type="checkbox" value="1" {{$options->where('key', 'subscription_users')->first()->value == '1' ? 'checked' : '' }}>
                      Новый пользователь
                    </label></div>
            </div>

            <div class="form-group form-element-checkbox ">
                <div class="checkbox"><label>
                        <input id="subscription_proposals" name="subscription_proposals"
                               type="checkbox" value="1" {{$options->where('key', 'subscription_proposals')->first()->value == '1' ? 'checked' : '' }}>
                        Новая заявка
                    </label></div>
            </div>

            <div class="form-group form-element-checkbox ">
                <div class="checkbox"><label>
                        <input id="subscription_orders" name="subscription_orders"
                               type="checkbox" value="1" {{$options->where('key', 'subscription_orders')->first()->value == '1' ? 'checked' : '' }}>
                        Новый заказ
                    </label></div>
            </div>

            <div class="form-group form-element-checkbox ">
                <div class="checkbox"><label>
                        <input id="subscription_machines" name="subscription_machines"
                               type="checkbox" value="1" {{$options->where('key', 'subscription_machines')->first()->value == '1' ? 'checked' : '' }}>
                        Новая техника
                    </label></div>
            </div>
        </div>
    </div>
</div>