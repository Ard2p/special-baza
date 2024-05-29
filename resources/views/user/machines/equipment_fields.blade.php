@if($equipments->count())
<div class="show-if-number">
    <div class="col roller-item">
        <div class="item">
            <i class="fas fa-plus active"></i>
            <i class="fas fa-minus"></i>
            <h4>Доп. оборудование</h4>
        </div>
        <div class="content">
            @foreach($equipments as $equipment)
                <div class="col-md-12">
                    <h3> - {{$equipment->name}}</h3>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="required">
                                Стоимость аренды, за час
                                <input type="text" name="sum_hour_eqip_{{$equipment->id}}"
                                       placeholder="* Стоимость аренды, за час" required>

                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="required">
                                Стоимость аренды, за смену
                                <input type="text" name="sum_day_eqip_{{$equipment->id}}"
                                       placeholder="* Стоимость аренды, за смену" required>
                            </label>
                        </div>
                    </div>
                @foreach($equipment->optional_fields as $option)
                    <div class="col-md-4">
                        <div class="form-item">
                            <label>
                                {{$option->current_locale_name}} ({{$option->unit->name ?? ''}})
                                <input type="text" name="option_equip{{$id}}_{{$option->id}}" value=""
                                       placeholder="">
                            </label>
                        </div>
                    </div>
                @endforeach
                    <hr>
                </div>

            @endforeach
        </div>
    </div>
</div>
    @endif
