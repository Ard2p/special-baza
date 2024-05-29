<div class="content body" id="tables_content">
    <div class="panel">
        <div class="panel-body">
            <h3>Создать контактную форму</h3>
            @if(Session::has('errors'))
                <div class="alert alert-warning">
                    <strong>Внимание!</strong>{!! Session::get('errors') !!}
                </div>
            @endif
            <form class="row" method="POST" action="{{route('contact-form.store')}}">
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
                            <input type="checkbox" name="include_sub" value="1" {{old('name') ? 'checked' : ''}}>Включить
                            подкатегории
                        </label>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="collect_comment" value="1" {{old('collect_comment') ? 'checked' : ''}}>Включить
                                Комментарий
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
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="collect_name"
                                       value="1" {{old('collect_name') ? 'checked' : ''}}>Собирать имена
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="collect_email"
                                       value="1" {{old('collect_email') ? 'checked' : ''}}>Собирать Email
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="collect_phone"
                                       value="1" {{old('collect_phone') ? 'checked' : ''}}>Собирать Телефон
                            </label>
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
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Шаблон</label>
                            <select style="width:100%;" class="form-control input-select"
                                    data-placeholder="Выберите шаблон..." name="template_id">
                                @foreach($templates as $template)
                                    <option value="{{$template->id}}" {{old('template_id') === $template->id ? 'selected' : ''}}>{{$template->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Шаблон SMS</label>
                            <select style="width:100%;" class="form-control input-select"
                                    data-placeholder="Выберите шаблон..." name="phone_template_id">
                                @foreach($templates_phone as $template)
                                    <option value="{{$template->id}}" {{old('phone_template_id') === $template->id ? 'selected' : ''}}>({{$template->type === 'phone' ? 'TXT' : 'HTML'}}) {{$template->name}}</option>
                                @endforeach
                            </select>
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