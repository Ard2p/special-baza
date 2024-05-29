<div class="content body">
    <div class="panel">
        <div class="panel-body">
            <ol class="breadcrumb">
                <li><a href="https://office.trans-baza.ru">Панель</a></li>
                <li class="active">Справочники</li>
                <li><a href="https://office.trans-baza.ru/types">Категория техники</a></li>
                <li><a href="https://office.trans-baza.ru/types/{{$equipment->type_id}}/edit">{{$equipment->category->name}}</a></li>
                <li class="active">Редактирование записи</li>
            </ol>

            <form action="{{route('equipment.update', $equipment->id)}}" method="post" class="form-elements">
                @csrf
                @method('PATCH')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-elements">
                            <div class="form-group form-element-text "><label for="name" class="control-label">
                                    Наименование

                                    <span class="form-element-required">*</span></label> <input type="text" id="name"
                                                                                                name="name"
                                                                                                value="{{$equipment->name}}"
                                                                                                class="form-control">
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Сохранить</button>
                    </div>
                    <div class="col-md-6">
                        <div class="form-elements"><h4>Дополнительные атрибуты</h4>
                            <task-template
                                    name-btn="{{'Добавить поле'}}"
                                    col-name="{{'fields'}}"
                                    field-name="{{'Наименование'}}"
                                    field-value="{{'Ед. Измерения'}}"
                                    default-params="{{json_encode([])}}"
                                    few-params="{{json_encode([])}}"
                                    unit="{{json_encode($units->toArray())}}"
                                    url="{{route('get_equipment_optional_attributes', $equipment->id)}}"
                            ></task-template>
                            <script type="text/x-template" id="fields-template">
                                <div class="params-helper">
                                    <div class="button" style="width: 200px;">
                                        <a href="#" class="btn btn-primary" @click.prevent="addParam" v-html="nameBtn"></a>
                                    </div>

                                    <div class="form" v-for="(param, i) in paramsList">
                                        <input type="hidden" v-model="param.id">
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label for="" v-html="fieldName">
                                                </label>
                                                <input type="text" class="form-control" v-model="param.name">
                                            </div>
                                        </div>
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label for="" v-html="fieldValue">

                                                </label>
                                                <select type="text" class="form-control" v-model="param.unit_id">
                                                    <option value="0" selected>Не указано</option>
                                                    <option v-for="unit in units"  :value="unit.id" v-html="unit.name"></option>
                                                </select>
                                            </div>
                                        </div>
                                        {{--   <div class="col-xs-3">
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
                                           </div>--}}
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label for="" v-html="">
                                                    Приоритет
                                                </label>
                                                <input type="number" class="form-control" v-model="param.priority">
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
                                    <input type="hidden" :name="colName" :value="JSON.stringify(paramsList)">
                                </div>
                            </script>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>