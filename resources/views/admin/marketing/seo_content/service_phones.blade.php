<h4>Справочник телефонов</h4>
<task-template
        name-btn="{{'Добавить поле'}}"
        phones="{{json_encode($seo->editable_content)}}"
        url="{{route('get_seo_service_phones', $seo->id)}}"
></task-template>

<script type="text/x-template" id="fields-template">
    <div class="params-helper">
        <div class="button" style="width: 200px;">
            <a href="#" class="btn btn-primary" @click.prevent="addParam" v-html="nameBtn"></a>
        </div>

        <div class="form" v-for="(param, i) in paramsList">
            <div class="col-xs-6">
                <div class="form-group">
                    <label for="">
                        Имя
                    </label>
                    <input type="text" class="form-control" v-model="param.name">
                </div>
            </div>
            <div class="col-xs-6">
                <div class="form-group">
                    <label for="">
                        Телефон
                    </label>
                    <input type="text" class="form-control" v-model="param.phone">
                </div>
            </div>
            <div class="col-xs-12">
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
    </div>
</script>
