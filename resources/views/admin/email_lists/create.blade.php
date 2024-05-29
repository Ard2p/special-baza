<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="panel">
        <div class="panel-body">
            <form class="row" id="user_filter">
                <div class="col-md-12">
                    <h3>Создать список {{$request->type === 'phone' ? 'Телефон' : 'Email'}}</h3>
                    <div class="col-md-6">
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Есть в списке</label>
                            <select style="width:100%;" class="form-control input-select "
                                    name="list_id">
                                <option value="0">Выберите список...</option>
                                @foreach($lists as $list)
                                    <option value="{{$list->id}}">{{$list->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Роль</label>
                            <select style="width:100%;" class="form-control input-select column-filter"
                                    name="role_id[]" multiple="multiple">
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}">{{$role->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Категория техники</label>
                            <select style="width:100%;" class="form-control input-select column-filter"
                                    name="type_id[]" multiple>
                                @foreach($categories as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                @endforeach
                            </select>
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
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="email_confirm" value="1">Подтвержден Email
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="phone_confirm" value="1">Подтвержден Телефон
                            </label>
                        </div>

                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button type="button" class="btn btn-info" id="show_user_filter">Показать
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button type="button" class="btn btn-success" data-toggle="modal"
                                        data-target="#create_list">Создать список
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <hr>
            <div class="table-responsive">
                <table class="table" id="users_table">
                    <thead>
                    <th><input type="checkbox" class="selectAll"></th>
                    <th>Пользователь</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal" id="create_list" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Создать список</h4>
            </div>
            <div class="modal-body">
                <form id="create_list_form" action="{{route('create_mailing_list')}}">
                    @csrf
                    <p>Будут добавлены выбраные строки из таблицы пользователей</p>
                    <div class="form-group">
                        <label>Наименование списка {{$request->type === 'phone' ? 'телефонов' : 'Email'}}</label>
                        <input class="form-control" type="text" name="name">
                    </div>
                    <input type="hidden" name="type" value="{{$request->type === 'phone' ? 'phone' : 'email'}}">

                    <div class="form-group">
                        <div class="col col-long">
                            <div class="btn-col">
                                <div class="image-load">
                                    <div class="button">
                                        <label style="    display: block;">
                                                <span class="btn btn-primary btn-block"
                                                      style="padding: 0 20px;">Импорт из файла</span>
                                            <input type="file" onchange="updateFile('phone')"
                                                   name="excel"
                                                   id="phone_file"
                                                   style="display: none;">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning example_import" data-type="{{$request->type === 'phone' ? 'phone' : 'email'}}">Шаблнон
                                импорта {{$request->type === 'phone' ? 'Телефонов' : 'Email'}}
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="ids">
                </form>
            </div>

            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-primary" id="create_list_name_btn">Создать список</button>
                <button type="button" class="btn btn-custom" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<form style="display: none" id="example_import_form" action="{{route('export_example_form')}}" method="POST">
    @csrf
    <input type="text" name="type" value="">
</form>

<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        var users_table_url = '/mailings?get_users=1';
        var users_table = $('#users_table').DataTable({
            "ajax": users_table_url,
            language: data_table_lang,
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
                    "data": "id",
                },
                {
                    "data": "email",
                },
                {
                    "data": "phone",
                },

            ],
        })
        $(".selectAll").on("click", function (e) {
            if ($(this).is(":checked")) {
                users_table.rows().select();
            } else {
                users_table.rows().deselect();
            }
        });

        $(document).on('click', '#show_user_filter', function () {
            var $form = $('#user_filter').serialize()
            users_table.ajax.url(users_table_url + '&' + $form).load()

        })
        $(document).on('click', '#create_list_name_btn', function () {
            var $form = $('#create_list_form')
            $form.find('[name=ids]').val(getSelected('users'))
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                success: function (e) {

                    swal(e.message)
                    setTimeout(function () {
                        location.href = e.url
                    }, 2000)
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
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
    });
</script>
