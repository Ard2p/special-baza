<div class="content body" id="tables_content">
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#ru_locale" aria-controls="all" role="tab"
                                                      data-toggle="tab">
                    Конатктная форма
                </a></li>
            @foreach (\App\Option::$systemLocales as $locale)
                <li role="presentation"><a href="#{{$locale}}_locale" aria-controls="all" role="tab"
                                           data-toggle="tab">
                        {{$locale}} Локализация
                    </a></li>

            @endforeach
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="ru_locale" class="tab-pane in active">

                <div class="panel">
                    <div class="panel-body">
                        <h3>Изменить контактную форму</h3>
                        @if(Session::has('errors'))
                            <div class="alert alert-warning">
                                <strong>Внимание!</strong>{!! Session::get('errors') !!}
                            </div>
                        @endif
                        <form class="row" method="POST" action="{{route('contact-form.update', $form->id)}}">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Наименование ФСК</label>
                                        <input class="form-control" type="text" value="{{$form->name}}" name="name">
                                    </div>

                                    <div class="form-group">
                                        <label>URL адрес</label>
                                        <input class="form-control" type="text" value="{{$form->url}}" name="url">
                                    </div>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="include_sub"
                                               value="1" {{$form->include_sub ? 'checked' : ''}}>Включить
                                        подкатегории
                                    </label>

                                    <div class="form-group">
                                        <label>Текст кнопки</label>
                                        <input class="form-control" type="text" name="button_text"
                                               value="{{$form->button_text}}">
                                    </div>
                                    <div class="form-group">
                                        <label>Текст формы</label>
                                        <textarea id="html" class="form-control" type="text"
                                                  name="form_text">{{$form->form_text}}</textarea>
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="collect_name"
                                                   value="1" {{$form->collect_name ? 'checked' : ''}}>Собирать имена
                                        </label>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="collect_email"
                                                   value="1" {{$form->collect_email ? 'checked' : ''}}>Собирать Email
                                        </label>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="collect_phone"
                                                   value="1" {{$form->collect_phone ? 'checked' : ''}}>Собирать Телефон
                                        </label>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="collect_comment"
                                                   value="1" {{$form->collect_comment ? 'checked' : ''}}>Включить
                                            Комментарий
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>Название текстового поля</label>
                                        <input class="form-control" type="text" name="comment_label"
                                               value="{{$form->comment_label}}">
                                    </div>
                                    <b>Расположение</b>
                                    <div class="form-group">

                                        <label class="radio-inline">
                                            <input type="radio" name="position"
                                                   value="top" {{$form->position === 'top' ? 'checked' : ''}}>Верхнее
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="position"
                                                   value="bottom" {{$form->position === 'bottom' ? 'checked' : ''}}>Нижнее
                                        </label>
                                    </div>
                                    <div class="form-group form-element-dependentselect">
                                        <label class="control-label">Шаблон</label>
                                        <select style="width:100%;" class="form-control input-select"
                                                data-placeholder="Выберите шаблон..." name="template_id">
                                            @foreach($templates as $template)
                                                <option value="{{$template->id}}" {{$form->template_id === $template->id ? 'selected' : ''}}>{{$template->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group form-element-dependentselect">
                                        <label class="control-label">Шаблон SMS</label>
                                        <select style="width:100%;" class="form-control input-select"
                                                data-placeholder="Выберите шаблон..." name="phone_template_id">
                                            @foreach($templates_phone as $template)
                                                <option value="{{$template->id}}" {{$form->phone_template_id === $template->id ? 'selected' : ''}}>
                                                    ({{$template->type === 'phone' ? 'TXT' : 'HTML'}}
                                                    ) {{$template->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="is_publish"
                                                   value="1" {{$form->is_publish ? 'checked' : ''}}>Опубликовано
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>Цвет</label>
                                        <input class="form-control" type="color" value="{{$form->settings['color']}}"
                                               name="color">
                                    </div>
                                    <div class="form-group">
                                        <label>Граница</label>
                                        <input class="form-control" type="text" value="{{$form->settings['border']}}"
                                               name="border">
                                    </div>
                                    <a href="{{route('mailing_list.edit', $form->email_book->id)}}">Список Email
                                        ({{$form->email_book->emails->count()}})</a>
                                    <br>
                                    <a href="{{route('mailing_list.edit', $form->phone_book->id)}}">Список Телефон
                                        ({{$form->phone_book->phones->count()}})</a>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="btn-group">
                                            <button class="btn btn-primary" type="submit">Сохранить</button>
                                            <button class="btn btn-default" id="preview"
                                                    data-toggle="modal"
                                                    data-target="#preview_iframe"
                                                    type="button"><i class="fa fa-eye"></i> Превью
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <hr>
                    </div>
                    <div class="panel-group">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" href="#collapse1">Отправленые Email</a>


                                </h4>
                            </div>

                            <div id="collapse1" class="panel-collapse collapse">
                                <div class="panel-group">
                                    <div class="panel panel-default">
                                        <form class="row" id="action_form">
                                            <div class="col-md-12">

                                                <div class="col-md-6">
                                                    <div class="form-group form-element-dependentselect">
                                                        <label class="control-label"></label>
                                                        <select style="width:100%;" class="form-control input-select"
                                                                data-placeholder="Выберите действие..." name="do_it">
                                                            <option value="0">Выберите действие</option>
                                                            <option value="resend">Повторно отправить</option>
                                                            <option value="delete_list">Удалить из списка</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <div class="btn-group">
                                                            <button type="submit" class="btn btn-success"
                                                                    id="show_emails_filter">
                                                                Исполнить
                                                            </button>
                                                            <button type="button" class="btn btn-warning"
                                                                    id="export_emails">Экспорт
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>

                                        <form class="row" id="filter_form">
                                            <div class="col-md-12">
                                                <h4>Фильтры</h4>

                                                <div class="col-md-4">
                                                    <div class="form-group form-element-dependentselect">
                                                        <label class="control-label">Выберите Email</label>
                                                        <select style="width:100%;" class="form-control input-select"
                                                                data-placeholder="Выберите Emails..." name="email[]"
                                                                multiple>

                                                            @foreach($form->sendingMails->unique('email') as $mail)
                                                                <option value="{{$mail->email}}">{{$mail->email}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <b>Был клик</b>
                                                    <div class="form-group">

                                                        <label class="radio-inline">
                                                            <input type="radio" name="has_click"
                                                                   value="yes">Был клик (да или нет)
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input type="radio" name="has_click"
                                                                   value="no">Не было клика
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input type="radio" name="has_click"
                                                                   value="all" checked>Не важно (показать все)
                                                        </label>
                                                    </div>
                                                    <div class="form-group" id="show_click_type" style="display: none">
                                                        <label class="radio-inline">
                                                            <input type="radio" name="click_type"
                                                                   value="any">Любой
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input type="radio" name="click_type"
                                                                   value="yes">Клик-Да (интересно)
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input type="radio" name="click_type"
                                                                   value="no" checked>Клик-Нет (неинтересно)
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="input-date form-group input-group"
                                                         style="width: 150px;">
                                                        <input name="from"
                                                               data-date-format="DD.MM.YYYY"
                                                               data-date-useseconds="false"
                                                               type="text"
                                                               placeholder="Начиная с" class="form-control">
                                                        <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                                                    </div>
                                                    <div class="input-date form-group input-group"
                                                         style="width: 150px;">
                                                        <input name="to"
                                                               data-date-format="DD.MM.YYYY"
                                                               data-date-useseconds="false"
                                                               type="text"
                                                               placeholder="Заканчивая" class="form-control">
                                                        <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-info" id="show_filter">
                                                                Показать
                                                            </button>
                                                            <button type="button" class="btn btn-default"
                                                                    id="clear_filter">Очистить
                                                                фильтр
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table" id="sending_table" width="100%">
                                        <thead>
                                        <th class="text-center"><input type="checkbox" class="selectAll"></th>
                                        <th>Email</th>
                                        <th>Дата отправления</th>
                                        <th>Время просмотра</th>
                                        <th>Письмо полезно?</th>
                                        <th>Комментарий</th>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>


                                </div>
                            </div>

                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" href="#collapse2">Отправленые SMS</a>
                                </h4>
                            </div>
                            <div id="collapse2" class="panel-collapse collapse">
                                <div class="panel-group">
                                    <div class="panel panel-default">
                                        <form class="row" id="action_sms_form">
                                            <div class="col-md-12">

                                                <div class="col-md-6">
                                                    <div class="form-group form-element-dependentselect">
                                                        <label class="control-label"></label>
                                                        <select style="width:100%;" class="form-control input-select"
                                                                data-placeholder="Выберите действие..."
                                                                name="do_it_sms">
                                                            <option value="0">Выберите действие</option>
                                                            <option value="resend">Повторно отправить</option>
                                                            <option value="delete_list">Удалить из списка</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <div class="btn-group">
                                                            <button type="submit" class="btn btn-success"
                                                                    id="show_sms_filter">
                                                                Исполнить
                                                            </button>
                                                            <button type="button" class="btn btn-warning"
                                                                    id="export_sms">Экспорт
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>

                                        <form class="row" id="filter_sms_form">
                                            <div class="col-md-12">
                                                <h4>Фильтры</h4>

                                                <div class="col-md-4">
                                                    <div class="form-group form-element-dependentselect">
                                                        <label class="control-label">Выберите телефон</label>
                                                        <select style="width:100%;" class="form-control input-select"
                                                                data-placeholder="Выберите Emails..." name="phone[]"
                                                                multiple>

                                                            @foreach($form->sendingSms->unique('phone') as $mail)
                                                                <option value="{{$mail->phone}}">{{$mail->phone}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <b>Был клик</b>
                                                    <div class="form-group">

                                                        <label class="radio-inline">
                                                            <input type="radio" name="has_click"
                                                                   value="yes">Да
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input type="radio" name="has_click"
                                                                   value="no">Нет
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="input-date form-group input-group"
                                                         style="width: 150px;">
                                                        <input name="from"
                                                               data-date-format="DD.MM.YYYY"
                                                               data-date-useseconds="false"
                                                               type="text"
                                                               placeholder="Начиная с" class="form-control">
                                                        <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                                                    </div>
                                                    <div class="input-date form-group input-group"
                                                         style="width: 150px;">
                                                        <input name="to"
                                                               data-date-format="DD.MM.YYYY"
                                                               data-date-useseconds="false"
                                                               type="text"
                                                               placeholder="Заканчивая" class="form-control">
                                                        <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-info"
                                                                    id="show_sms_filter">Показать
                                                            </button>
                                                            <button type="button" class="btn btn-default"
                                                                    id="clear_sms_filter">
                                                                Очистить
                                                                фильтр
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table" id="sending_sms_table" width="100%">
                                        <thead>
                                        <th class="text-center"><input type="checkbox" class="selectAllPhones"></th>
                                        <th>Телефон</th>
                                        <th>Дата отправления</th>
                                        <th>Доставлено</th>
                                        <th>Время просмотра</th>
                                        <th>Клик</th>
                                        <th>Комментарий</th>

                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @foreach (\App\Option::$systemLocales as $locale)
                @php
                    $en = $form->locale()->whereLocale($locale)->first();
                @endphp
                <div role="tabpanel" id="{{$locale}}_locale" class="tab-pane">
                    @if($en)
                        {!! view('admin.marketing.contact_form.edit_locale', ['locale' => $locale, 'templates' => $templates, 'localization' => $en, 'contact_id'=> $form->id])->render()  !!}
                    @else
                        {!! view('admin.marketing.contact_form.create_locale', ['locale' => $locale, 'templates' => $templates, 'contact_id'=> $form->id])->render()  !!}
                    @endif
                </div>

            @endforeach
        </div>
    </div>
</div>
<div class="modal" id="preview_iframe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Превью</h4>
            </div>
            <div class="modal-body" id="__iframe">

            </div>

            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-custom" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<script>
    var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    document.addEventListener("DOMContentLoaded", function (event) {
        var data_table_url = '{{route('contact-form.edit', [$form->id, 'get_sending' => 1])}}';
        var data_table = $('#sending_table').DataTable({
            language: data_table_lang,
            ajax: data_table_url,
            "autoWidth": false,
            "ordering": false,
            columnDefs: [{
                orderable: false,
                className: 'select-checkbox',
                targets: 0
            }],
            select: {
                style: 'multi',
            },
            "columns": [
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            //data = '<input type="checkbox" class="friend_check" value="' + full['id'] + '">';

                        }
                        return data;
                    },
                },
                {
                    "data": "email",
                },

                {
                    "data": "created_at",
                },
                {
                    "data": "watch_at",
                },
                {
                    "data": "status",
                },
                {
                    "data": "comment",
                },

            ]
        });
        $(document).on('submit', '#action_form', function (e) {
            e.preventDefault()
            let ids = getSelected();
            let action = $('[name=do_it]').val();
            if (!(ids.length > 0) || action == '0') {
                swal('Ничего не выбрано!')
                return
            }
            swal({
                title: 'Внимание!',
                text: "Вы подтверждаете данное действие?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Да!',
                cancelButtonText: 'Нет, отмена!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '{{route('contact_table_action')}}',
                        type: 'POST',
                        data: {
                            _token: '{{csrf_token()}}',
                            ids: ids,
                            action: action
                        },
                        success: function (e) {
                            data_table.ajax.reload();
                            Admin.Messages.success('Успешно!', e.message)
                        },
                        error: function () {

                        }
                    })
                }
            })
        })
        $(".selectAll").on("click", function (e) {
            if ($(this).is(":checked")) {
                data_table.rows().select();
            } else {
                data_table.rows().deselect();
            }
        });

        $(document).on('click', '#show_filter', function () {
            data_table.ajax.url(data_table_url + '&' + $('#filter_form').serialize()).load()

        })

        function getSelected() {
            var selected = data_table.rows({selected: true}).data()

            var arr = [];
            var row;
            selected = Object.values(selected)
            for (row in selected) {

                if (selected[row]['id'] !== undefined) {
                    arr.push(selected[row]['id'])
                }
            }
            return arr
        }

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
        @foreach (\App\Option::$systemLocales as $locale)
        Admin.WYSIWYG.switchOn('html_{{$locale}}', 'ckeditor', {
            'removeButtons': 'Save',
            'language': 'en',
            "height": 200,
            "script": true,
            "allowedContent": true,
            "extraPlugins": "panelbutton,uploadimage,image2,justify,youtube,uploadfile,colorbutton",
            "uploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token,
            "filebrowserUploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token
        })
        @endforeach
        $(document).on('click', '#clear_filter', function () {
            data_table.ajax.url(data_table_url).load()
        })
        $(document).on('click', '#preview', function () {
            CKEDITOR.instances.html.updateElement();
            iframe(0)

            var result = {};
            $.each($('form').serializeArray(), function () {
                result[this.name] = this.value;
            });
            console.log(result);
            postToIframe(result, '/preview-form/0', 'target_iframe')
        })

        function postToIframe(data, url, target) {
            $('body').append('<form action="' + url + '" method="post" target="' + target + '" id="postToIframe"></form>');
            $.each(data, function (n, v) {
                $('#postToIframe').append('<textarea name="' + n + '" />' + v + '</textarea>');
                $('body').find('#postToIframe [name=' + n + ']').val(v);
            });
            $('#postToIframe').submit().remove();
        }

        function iframe(id) {
            $('#__iframe').html('')
            var link = "/preview-form/";
            var _iframe = document.createElement('iframe');
            _iframe.width = "100%";
            _iframe.name = "target_iframe";
            _iframe.scrolling = "yes";
            _iframe.style.height = "400px";
            _iframe.setAttribute("src", link);
            document.getElementById("__iframe").appendChild(_iframe);

        }

        $(document).on('click', '#export_emails', function () {
            $('#export_form [name=type]').val('email')
            $('#export_form').submit()
        })

        $(document).on('change', '[name=has_click]', function () {
            if (this.value == 'yes') {
                $('#show_click_type').show()
            } else {
                $('#show_click_type').hide()
            }
        })
        /*-----------------------PHONES------------------------------*/
        var phone_table_url = '{!!  route('contact-form.edit', [$form->id, 'get_sending' => 1, 'type_sms' => 1])!!}';
        var phone_table = $('#sending_sms_table').DataTable({
            language: data_table_lang,
            ajax: phone_table_url,
            "autoWidth": false,
            "ordering": false,
            columnDefs: [{
                orderable: false,
                className: 'select-checkbox',
                targets: 0
            }],
            select: {
                style: 'multi',
            },
            "columns": [
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            //data = '<input type="checkbox" class="friend_check" value="' + full['id'] + '">';

                        }
                        return data;
                    },
                },
                {
                    "data": "phone",
                },

                {
                    "data": "created_at",
                },
                {
                    "data": "delivery_status",
                },
                {
                    "data": "watch_at",
                },
                {
                    "data": "status",
                },
                {
                    "data": "comment",
                },

            ]
        });
        $(document).on('submit', '#action_sms_form', function (e) {
            e.preventDefault()
            let ids = getSelectedPhones();
            let action = $('[name=do_it_sms]').val();
            if (!(ids.length > 0) || action == '0') {
                swal('Ничего не выбрано!')
                return
            }
            swal({
                title: 'Внимание!',
                text: "Вы подтверждаете данное действие?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Да!',
                cancelButtonText: 'Нет, отмена!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '{{route('contact_table_sms_action')}}',
                        type: 'POST',
                        data: {
                            _token: '{{csrf_token()}}',
                            ids: ids,
                            action: action
                        },
                        success: function (e) {
                            phone_table.ajax.reload();
                            Admin.Messages.success('Успешно!', e.message)
                        },
                        error: function () {

                        }
                    })
                }
            })
        })
        $(".selectAllPhones").on("click", function (e) {
            if ($(this).is(":checked")) {
                phone_table.rows().select();
            } else {
                phone_table.rows().deselect();
            }
        });

        $(document).on('click', '#clear_sms_filter', function () {
            phone_table.ajax.url(phone_table_url).load()
        })

        $(document).on('click', '#show_sms_filter', function () {
            phone_table.ajax.url(phone_table_url + '&' + $('#filter_sms_form').serialize()).load()

        })

        function getSelectedPhones() {
            var selected = phone_table.rows({selected: true}).data()

            var arr = [];
            var row;
            selected = Object.values(selected)
            for (row in selected) {

                if (selected[row]['id'] !== undefined) {
                    arr.push(selected[row]['id'])
                }
            }
            return arr
        }

        $(document).on('click', '#export_sms', function () {
            $('#export_form [name=type]').val('phone')
            $('#export_form').submit()
        })

    });
</script>
<form style="display: none" id="export_form" action="{{route('export_sending_form', $form->id)}}" method="POST">
    @csrf
    <input type="text" name="type" value="">
</form>