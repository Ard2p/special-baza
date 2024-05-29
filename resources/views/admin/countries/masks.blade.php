<h4>Телефонные маски</h4>
<task-template
        name-btn="{{'Добавить поле'}}"
        col-name="{{'fields'}}"
        field-name="{{'Маска'}}"
        field-value="{{'Ед. Измерения'}}"
        default-params="{{json_encode([])}}"
        few-params="{{json_encode([])}}"
        url="{{route('get_country_phone_mask', $id)}}"
></task-template>

<h4>Автомобильные маски</h4>
<task-template
        name-btn="{{'Добавить поле'}}"
        col-name="{{'fields'}}"
        field-name="{{'Маска'}}"
        img-enable="1"
        field-value="{{'Ед. Измерения'}}"
        default-params="{{json_encode([])}}"
        few-params="{{json_encode([])}}"
        img-url="{{json_encode(route('machinery.load-admin-files'))}}"
        url="{{route('get_country_machine_mask', $id)}}"
></task-template>

<script type="text/x-template" id="fields-template">
    <div class="params-helper">
        <div class="button" style="width: 200px;">
            <a href="#" class="btn btn-primary" @click.prevent="addParam" v-html="nameBtn"></a>
        </div>

        <div class="form" v-for="(param, i) in paramsList">
            <input type="hidden" v-model="param.id">
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="" v-html="fieldName">
                    </label>
                    <input type="text" class="form-control" v-model="param.mask">
                </div>
            </div>
            <div class="col-sm-6" v-if="img_enable">
                <div class="image-load">
                    <div class="button btn btn-primary" v-if="!param.image">
                        <label :for="'col-' + i">
                            <span class="btn-custom" style="padding: 0 20px;">Добавить фото</span>
                            <input type="file" :id="'col-' + i" style="display: none;" @change="loadPhoto($event, param)">
                        </label>
                    </div>
                    <input type="hidden" v-model="param.image" v-if="!imagesArr || !imagesArr.length">

                    <div class="row fancy-container">
                        <div class="col-md-4" v-if="param.image">
                            <span class="delete" @click="delImage(i)"></span>
                            <a class="thumbnail fancybox" rel="ligthbox" :href="'/' + param.image">
                                <img class="img-responsive" alt="" :src="'/' + param.image"/>
                            </a>
                        </div>
                    </div>
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
