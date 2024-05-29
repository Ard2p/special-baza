<h4>Дополнительные атрибуты</h4>
<task-template
        name-btn="{{'Добавить поле'}}"
        col-name="{{'fields'}}"
        field-name="{{'Наименование'}}"
        field-value="{{'Ед. Измерения'}}"
        default-params="{{json_encode([])}}"
        few-params="{{json_encode([])}}"
        unit="{{json_encode($units->toArray())}}"
        locale="{{json_encode(\App\Option::$systemLocales)}}"
        url="{{route('get_optional_attributes', $id)}}"
></task-template>

<script type="text/x-template" id="fields-template">
    <div class="params-helper">
        <div class="button" style="width: 200px;">
            <a href="#" class="btn btn-primary" @click.prevent="addParam" v-html="nameBtn"></a>
        </div>

        <div class="form" v-for="(param, i) in paramsList">
            <input type="hidden" v-model="param.id">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="" v-html="fieldName">
                    </label>
                    <input type="text" class="form-control" v-model="param.name">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="" v-html="fieldValue">

                    </label>
                    <select type="text" class="form-control" v-model="param.unit_id">
                        <option value="0" selected>Не указано</option>
                        <option v-for="unit in units"  :value="unit.id" v-html="unit.name"></option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="">
                        Тип поля
                    </label>
                    <select type="text" class="form-control" v-model="param.field">
                        <option value="date">Дата</option>
                        <option value="text">Текст</option>
                        <option value="number">Число</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="" v-html="">
                        Приоритет
                    </label>
                    <input type="number" class="form-control" v-model="param.priority">
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-sm-3" v-for="loc in locales">
                <div class="form-group">
                    <label v-html="'Локализация ' + loc">
                    </label>
                    <input type="text" class="form-control" v-model="param['locales'][loc]">

                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-sm-12">
                <div class="form-group ">
                    <button href="#" class="btn btn-danger btn-xs" @click.prevent="delItem(i)"><i
                                class="fa fa-trash"></i>
                        Удалить
                    </button>
                </div>
            </div>
            <hr>
        </div>
        <div class="btn-group">
            <button class="btn btn-success" v-if="paramsList.length > 0" @click.prevent="save">Сохранить</button>
            <button class="btn btn-danger" v-if="paramsList.length > 0" @click.prevent="refuse">Отмена</button>
        </div>
        <input type="hidden" :name="colName" :value="JSON.stringify(paramsList)">
    </div>
</script>
