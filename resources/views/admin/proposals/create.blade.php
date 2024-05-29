<div class="content body" id="tables_content">
    <div class="panel">
        <div class="panel-body">
            <h3>Создать форму простой заявки</h3>
            @if(Session::has('errors'))
                <div class="alert alert-warning">
                    <strong>Внимание!</strong>{!! Session::get('errors') !!}
                </div>
            @endif
            <form class="row" method="POST" action="{{route('simple-proposal.store')}}">
                @csrf
                <div class="col-md-12">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Наименование формы</label>
                            <input class="form-control" type="text" name="name" value="{{old('name')}}">
                        </div>

                        <div class="form-group">
                            <label>URL адрес</label>
                            <input class="form-control" type="text" name="url" value="{{old('url')}}">
                        </div>
                        <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="include_sub" value="1" {{old('include_sub') ? 'checked' : ''}}>Включить
                            подкатегории
                        </label>
                        </div>

                        <div class="form-group">
                            <label>Текст лейбла "комменатрии"</label>
                            <input class="form-control" type="text" name="comment_label" value="{{old('comment_label')}}">
                        </div>
                        <div class="form-group">
                            <label>Текст кнопки</label>
                            <input class="form-control" type="text" name="button_text" value="{{old('button_text')}}">
                        </div>
                        <div class="form-group">
                            <label>Текст формы</label>
                            <textarea class="form-control" id="html" type="text" name="form_text">{{old('form_text')}}</textarea>
                        </div>

                    </div>
                    <div class="col-md-6">
                        </div>
                        <b>Расположение</b>
                        <div class="form-group">
                            <label class="radio-inline">
                                <input type="radio" name="position"
                                       value="top" {{old('position') === 'top' ? 'checked' : ''}}>Верхнее
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="position"
                                       value="bottom" {{old('position') === 'bottom' ? 'checked' : ''}}>Нижнее
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button class="btn btn-primary" type="submit">Сохранить</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <hr>

        </div>
    </div>

</div>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        var token = document.head.querySelector('meta[name="csrf-token"]').content;
        Admin.WYSIWYG.switchOn('html', 'ckeditor', {
            'removeButtons': 'Save',
            'language': 'en',
            "height": 200,
            "script": true,
            "allowedContent": true,
            "extraPlugins": "panelbutton,uploadimage,image2,justify,youtube,uploadfile,colorbutton",
            "uploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token,
            "filebrowserUploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token
        })
    });
</script>