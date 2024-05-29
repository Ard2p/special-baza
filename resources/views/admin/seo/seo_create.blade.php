<form method="POST" id="create_seo" action="{{route('seo_blocks.store')}}" class="panel panel-default">
    @csrf
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};

    </script>

    <div class="form-elements">
        <div class="panel-body">
            <h3>Добавить Seo</h3>
            <div class="form-elements">
                <div class="row">

                    <div class="col-md-12">
                        <h4> Получить URL по фильтру.</h4>
                        <div class="col-md-6">
                            <div class="form-group form-element-dependentselect">
                                <label class="control-label">Категория техники</label>
                                <select style="width:100%;" class="form-control input-select column-filter"
                                        name="type_id">
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group"><label class="radio-inline">
                                    <input type="radio" name="url_type"
                                           value="phone">Телефонный
                                    справочник
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="url_type"
                                           value="spec" checked>Спецтехника
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="url_type"
                                           value="public">Публичная страница
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group form-element-dependentselect">
                                <label class="control-label">Регион</label>
                                <select style="width:100%;"
                                        class="form-control input-select column-filter input-select-dependent"
                                        id="region_id"
                                        data-select-type="single"
                                        data-url="https://office.trans-baza.ru/machineries/dependent-select/region_id/3"
                                        data-depends="[]"
                                        name="region_id">
                                    <option value="0">Все</option>
                                    @foreach($regions as $region)
                                        <option value="{{$region->id}}">{{$region->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group form-element-dependentselect ">
                                <label for="city_id" class="control-label">
                                    Город

                                    <span class="form-element-required">*</span>
                                </label>

                                <div>
                                    <select id="city_id" size="2" data-select-type="single"
                                            data-url="{{route('dep_drop')}}"
                                            data-depends="[&quot;region_id&quot;]"
                                            class="form-control input-select input-select-dependent"
                                            name="city_id">
                                        <option value="">Выберите город</option>
                                    </select>
                                </div>


                            </div>

                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="btn-group">
                                    <button type="button" data-action="{{route('url_help')}}" class="btn btn-info"
                                            id="get_url">Получить Url
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group form-element-text "><label for="email" class="control-label">
                                URL страницы

                                <span class="form-element-required">*</span></label>
                            <input type="text"
                                   name="url"
                                   value=""
                                   class="form-control"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group form-element-text "><label for="email" class="control-label">
                                Примечание
                            </label> <input type="text"
                                            name="comment"
                                            value=""
                                            class="form-control"></div>
                        <div class="form-group form-element-checkbox ">
                            <div class="checkbox"><label><input id="is_active" name="is_active" type="checkbox"
                                                                value="1" checked>

                                    Опубликовано
                                </label></div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-elements">
                            <div class="form-group form-element-wysiwyg ">
                                <label for="content" class="control-label">
                                    Верхний блок
                                </label>
                                <textarea id="top_seo" name="html_top" cols="50" rows="10"></textarea>
                            </div>
                        </div>

                        <div class="form-elements">
                            <div class="form-group form-element-wysiwyg ">
                                <label for="content" class="control-label">
                                    Нижний блок
                                </label>


                                <textarea id="bottom_seo" name="html_bottom" cols="50" rows="10"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-buttons panel-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Сохранить
            </button>
        </div>
    </div>

</form>
