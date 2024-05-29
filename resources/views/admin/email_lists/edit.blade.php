<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="panel">
        <div class="panel-body">
            <h3>Редактировать список {{$list->name}}</h3>
            <form class="row" method="POST" action="{{route('mailing_list.update', $list->id)}}">
                @csrf
                @method('PATCH')
                <input type="hidden" value="{{$list->type}}" name="type">
                <div class="col-md-12">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Наименование списка</label>
                            <input class="form-control" type="text" value="{{$list->name}}" name="name">
                        </div>

                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button class="btn btn-primary" type="submit">Сохранить</button>
                                <button type="button" class="btn btn-info" data-toggle="modal"
                                        data-target="#add_to_list">Добавить в список
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <hr>
            <div class="table-responsive">
                <table class="table" width="100%" id="data_table">
                    <thead>
                    <th class="text-center"><input type="checkbox" class="selectAll"></th>
                    <th>Пользователь</th>
                    <th>{{$list->type === 'phone' ? 'Телефон' : 'Email'}}</th>
                    @if($list->contact_form && $list->contact_form->collect_comment)
                        <th>Комментарий</th>
                    @endif
                    <th>Дата добавления</th>

                    <th class="text-center"><i class="fa fa-cog"></i></th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<div class="modal" id="add_to_list" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Добавить в список</h4>
            </div>
            <div class="modal-body">
                <form id="add_to_list_form" action="{{route('add_to_name_list')}}">
                    @csrf
                    <input type="hidden" name="ids" value="">
                    <input type="hidden" name="id" value="{{$list->id}}">
                    <div class="form-group form-element-dependentselect">
                        <label class="control-label">Из списка</label>
                        <select style="width:100%;" class="form-control input-select column-filter"
                                data-placeholder="Выберите список" name="lists[]" multiple>
                            @foreach($lists as $item)
                                <option value="{{$item->id}}">{{$item->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-element-dependentselect">
                        <label class="control-label">Из динамического списка</label>
                        <select style="width:100%;" class="form-control input-select column-filter"
                                data-placeholder="Выберите список" name="dynamic[]" multiple>
                            @foreach($filters as $item)
                                <option value="{{$item->id}}">{{$item->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="col col-long">
                            <div class="btn-col">
                                <div class="image-load">
                                    <div class="button">
                                        <label style="    display: block;">
                                                <span class="btn btn-primary btn-block"
                                                      style="padding: 0 20px;">Импорт из файла</span>
                                            <input type="file" onchange="updateFile('email')"
                                                   name="excel"
                                                   id="email_file"
                                                   style="display: none;">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning example_import"
                                    data-type="{{$list->type === 'phone' ? 'phone' : 'email'}}">Шаблнон
                                импорта {{$list->type === 'phone' ? 'Телефонов' : 'Email'}}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-info" id="save_add_to_list">Добавить</button>
                <button type="button" class="btn btn-custom" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {

        var data_table_url = '{{route('mailing_list.show', $list->id)}}';
        var data_table = $('#data_table').DataTable({
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
                selector: 'td:first-child'
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
                    "data": "user_name",
                },

                {
                    "data": "{{$list->type === 'phone' ? 'phone' : 'email'}}",
                },
            @if($list->contact_form && $list->contact_form->collect_comment)
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<button type="button" class="btn btn-info" data-list="{{$list->id}}">Комментарии</button>';

                        }
                        return data;
                    },
                },
            @endif
                {
                    "data": "pivot.created_at",
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<button type="button" class="btn btn-danger detach_email" data-list="{{$list->id}}" data-id="' + full['id'] + '">Удалить из списка</button>';

                        }
                        return data;
                    },
                },

            ]

        })
        $(".selectAll").on("click", function (e) {
            if ($(this).is(":checked")) {
                data_table.rows().select();
            } else {
                data_table.rows().deselect();
            }
        });
        $(document).on('click', '.detach_email', function () {
            var id = $(this).data('id');
            var list_id = $(this).data('list');
                swal({
                    title: 'Внимание!',
                    text: "Убрать элемент из списка?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Да!',
                    cancelButtonText: 'Нет, отмена!'
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            url: '{{route('detach_email')}}',
                            type: 'POST',
                            data: {
                                id: id,
                                list_id: list_id,
                                _token: '{{csrf_token()}}'
                            },
                            success: function () {
                                Admin.Messages.success('Успешно!', 'Элемент удален из списка')
                                data_table.ajax.reload()
                            }
                        })
                    }
                })
        })
        updateFile = function (type) {
            var input = document.getElementById(type + '_file');

            for (var i = 0; i < input.files.length; ++i) {
                var _name = input.files.item(i).name;
                var array = _name.split('.');
                var extension = array[array.length - 1];
                var allow_extension = ['xls', 'xlsx', 'csv'];
                console.log(extension);
                if (!allow_extension.includes(extension)) {
                    alert('Внимание! Файл ' + _name + ' не будет обработан т.к. формат не поддерживается.')
                    //Array.prototype.slice.call(files).splice(i, 1);
                    return false;
                }
            }

        }

        function getSelected(type) {
            if (type === 'users') {
                var selected = users_table.rows({selected: true}).data()
            } else {

            }

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

        $(document).on('click', '.example_import', function (e) {
            e.preventDefault();
            var type = $(this).data('type')

            $('#example_import_form').find('[name=type]').val(type);
            $('#example_import_form').submit();
        })
        $(document).on('click', '#save_add_to_list', function () {
            let $form = $('#add_to_list_form')
            /*     $form.find('[name=ids]').val(getSelected('users'))*/
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                success: function (e) {
                    data_table.ajax.reload()
                    swal(e.message)
                    document.getElementById("email_file").value = "";
                    $('.modal').modal('hide');
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }

            })
        })
    });
</script>