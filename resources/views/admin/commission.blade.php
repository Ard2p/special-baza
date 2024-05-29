<div class="panel-body">
    <div class="form-elements">
        <div class="col-md-6">

            <div class="form-group form-elements">
                <label  class="control-label">
                   Комиссия сервиса (%)</label>
                <input type="number" name="system_commission" value="{{$options->where('key', 'system_commission')->first()->value / 100 }}" class="form-control"  pattern="[0-9]{2}">
            </div>

            <div class="form-group form-elements">
                <label  class="control-label">
                    Комиссия РП (%)</label>
                <input type="number" name="representative_commission" value="{{$options->where('key', 'representative_commission')->first()->value / 100 }}" class="form-control"  pattern="[0-9]{2}">
            </div>
            <div class="form-group form-elements">
                <label  class="control-label">
                    Доход виджета (% от заявки)</label>
                <input type="number" name="widget_commission" value="{{$options->where('key', 'widget_commission')->first()->value / 100 }}" class="form-control"  pattern="[0-9]{2}">
            </div>

        </div>
    </div>
</div>