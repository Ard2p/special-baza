<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="panel">
        <div class="panel-body">
            <h3>Подписка "{{$subscribe->name}}"</h3>
            <form class="row" method="POST" action="{{route('subscribe.update', $subscribe->id)}}">
                @csrf
                @method('PATCH')
                <div class="col-md-12">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Наименование подписки</label>
                            <input class="form-control" type="text" value="{{$subscribe->name}}" name="name">
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Роли</label>
                            <select style="width:100%;" class="form-control input-select"
                                    data-placeholder="Выберите роли..." name="roles[]" multiple>
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}" {{$subscribe->roles->contains($role) ? 'selected' : ''}}>{{$role->name}}</option>
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
            @if($subscribe->alias !== 'system')
                <div class="panel-group">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" href="#collapse12">Отписавшиеся пользователи</a>
                            </h4>
                        </div>

                        <div id="collapse12" class="panel-collapse collapse">
                            <div class="table-responsive">
                                <table class="table" width="100%">
                                    <thead>
                                    <th>Пользователь</th>
                                    </thead>
                                    <tbody>
                                    @foreach($subscribe->unsubscribes as $user)
                                        <tr>
                                            <td>{{$user->id_with_email}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            @endif
            @if($subscribe->alias !== 'news' && $subscribe->alias !== 'article')
                <div class="panel-group">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" href="#collapse1">Добавить шаблон</a>
                            </h4>
                        </div>
                        <div id="collapse1" class="panel-collapse collapse">
                            <form id="add_template" target="target_iframe"
                                  action="{{route('subscribe_templates.store')}}" method="post">
                                @csrf
                                <div class="panel-body">

                                    <input type="hidden" name="subscribe_id" value="{{$subscribe->id}}">
                                    <div class="form-elements">
                                        <div class="form-group">
                                            <label>Наименование</label>
                                            <input class="form-control" type="text" name="name">
                                        </div>
                                        <div class="form-group form-element-wysiwyg ">
                                            <label for="content" class="control-label">
                                                Шаблон
                                            </label>
                                            <textarea id="html" name="html_text" cols="50"
                                                      rows="10"></textarea>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="enable_stats" value="1">Отслеживать состояние
                                            рассылки
                                        </label>
                                    </div>
                                </div>
                                <div class="form-buttons panel-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Сохранить
                                    </button>

                                    <button type="button" class="btn btn-default preview_create" data-toggle="modal"
                                            data-target="#preview_iframe"><i class="fa fa-eye"></i> Превью
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
            <hr>
            <div class="table-responsive">
                <table class="table" width="100%" id="data_table">
                    <thead>
                    <th>Шаблон</th>
                    <th>Статус</th>
                    <th class="text-center"><i class="fa fa-cog"></i></th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<div class="modal" id="preview_iframe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
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
<div class="modal" id="sending_info" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Превью</h4>
            </div>
            <div class="modal-body" id="_sending_info">

            </div>

            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-custom" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {

        var data_table_url = '{{route('subscribe.show', [$subscribe->id, 'get_templates' => 1])}}';
        var data_table = $('#data_table').DataTable({
            language: data_table_lang,
            ajax: data_table_url,
            "autoWidth": false,
            "ordering": false,
            "columns": [
                {
                    "data": "name",
                },
                {
                    "data": "send",
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<button type="button" class="btn btn-primary preview_template"  data-toggle="modal"  data-target="#preview_iframe" data-id="' + full['id'] + '"><i class="fa fa-eye"></i></button>&nbsp;';
                            if (full['is_send'] === 0) {
                                data += '<button type="button" class="btn btn-success send" data-id="' + full['id'] + '">Отправить</button>&nbsp;';

                            } else {
                                data += '<button type="button" class="btn btn-info showInfo" data-toggle="modal"  data-target="#sending_info" data-url="' + full['info_url'] + '"><i class="fa fa-info"></i></button>&nbsp;';
                            }
                        }
                        return data;
                    },
                },

            ]

        })
        $(document).on('click', '.send', function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            swal({
                title: 'Внимание!',
                text: "Запустить рассылку?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Да!',
                cancelButtonText: 'Нет, отмена!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '{{route('send_subscribe')}}',
                        type: 'POST',
                        data: {
                            id: id,
                            _token: '{{csrf_token()}}'
                        },
                        success: function () {
                            data_table.ajax.reload();
                            Admin.Messages.success('Успешно!', 'Рассылка отправлена в обработку')
                        },
                        error: function () {

                        }
                    })
                }
            })
        })
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

        $(document).on('submit', '#add_template', function (e) {
            e.preventDefault();
            let $form = $(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function () {
                    data_table.ajax.reload();
                    $form[0].reset();
                    Admin.Messages.success('Успешно!', 'Шаблон добавлен')
                },
                error: function () {

                }
            })

        })
        $(document).on('click', '.showInfo', function () {
            var url = $(this).data('url');
            $('#_sending_info').html('')
            $.ajax({
                url: url,
                type: 'GET',
                success: function (e) {
                    $('#_sending_info').html(e.data)
                },
                error: function () {

                }
            })
        })
        $(document).on('click', '.preview_template', function () {
            iframe($(this).data('id'))
        })
        $(document).on('click', '.preview_create', function () {
            CKEDITOR.instances.html.updateElement();
            iframe(0)

            var result = {};
            $.each($('#add_template').serializeArray(), function () {
                result[this.name] = this.value;
            });
            console.log(result);
            postToIframe(result, '/preview-template/0', 'target_iframe')
        })

        function iframe(id) {
            $('#__iframe').html('')
            var link = "/preview-template/" + id;
            var _iframe = document.createElement('iframe');
            _iframe.width = "100%";
            _iframe.name = "target_iframe";
            _iframe.scrolling = "yes";
            _iframe.style.height = "400px";
            _iframe.setAttribute("src", link);
            document.getElementById("__iframe").appendChild(_iframe);

        }

        function postToIframe(data, url, target) {
            $('body').append('<form action="' + url + '" method="post" target="' + target + '" id="postToIframe"></form>');
            $.each(data, function (n, v) {
                $('#postToIframe').append('<textarea name="' + n + '" />' + v + '</textarea>');
                $('body').find('#postToIframe [name=' + n + ']').val(v);
            });
            $('#postToIframe').submit().remove();
        }
    });
</script>