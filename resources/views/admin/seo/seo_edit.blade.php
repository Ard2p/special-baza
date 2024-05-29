<form method="POST" id="edit_seo" action="{{route('seo_blocks.update', $block->id)}}" class="panel panel-default">
    @csrf
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};

    </script>

    <div class="form-elements">
        <div class="panel-body">
            <h3>Редактировать Seo</h3>
            <div class="form-elements">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group form-element-text "><label for="email" class="control-label">
                                URL страницы

                                <span class="form-element-required">*</span></label>
                            <input type="text"
                                   name="url"
                                   value="{{$block->url}}"
                                   class="form-control"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group form-element-text "><label for="email" class="control-label">
                                Примечание
                            </label> <input type="text"
                                            name="comment"
                                            value="{{$block->comment}}"
                                            class="form-control"></div>
                        <div class="form-group form-element-checkbox ">
                            <div class="checkbox"><label><input id="is_active" name="is_active" type="checkbox"
                                                                value="1" {{$block->is_active ? 'checked' : ''}}>

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
                                <textarea id="top_seo" name="html_top" cols="50"
                                          rows="10">{{$block->html_top}}</textarea>
                            </div>
                        </div>

                        <div class="form-elements">
                            <div class="form-group form-element-wysiwyg ">
                                <label for="content" class="control-label">
                                    Нижний блок
                                </label>


                                <textarea id="bottom_seo" name="html_bottom" cols="50"
                                          rows="10">{{$block->html_bottom}}</textarea>
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
