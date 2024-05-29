<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="panel">
        <div class="panel-body">
            <h3>Редактировать критерии отбора записей "{{$filter->name}}"</h3>
            <form class="row" method="POST" id="user_filter" action="{{route('filters.update', $filter->id)}}">
                @csrf
                <input type="hidden" name="type" value="{{$filter->type}}">
                @method('PATCH')
                <div class="col-md-12">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Наименование</label>
                            <input class="form-control" type="text" name="name" value="{{$filter->name}}">
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Есть в списке</label>
                            <select style="width:100%;" class="form-control input-select "
                                    name="list_id[]" multiple>
                                <option value="0">Выберите список...</option>
                                @foreach($lists as $list)
                                    <option value="{{$list->id}}" {{in_array($list->id, $filter->array['list_id'] ?? [])? 'selected' : ''}}>{{$list->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Роль</label>
                            <select style="width:100%;" class="form-control input-select column-filter"
                                    name="role_id[]" multiple="multiple">
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}" {{in_array($role->id, $filter->array['role_id'] ?? [])? 'selected' : ''}}>{{$role->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Категория техники</label>
                            <select style="width:100%;" class="form-control input-select column-filter"
                                    name="type_id[]" multiple>
                                @foreach($categories as $category)
                                    <option value="{{$category->id}}" {{in_array($category->id, ($filter->array['type_id'] ?? []))? 'selected' : ''}}>{{$category->name}}</option>
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
                                    <option value="{{$region->id}}" {{$region->id == $filter->array['region_id'] ? 'selected' : ''}}>{{$region->name}}</option>
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
                                    @if($_current_region)
                                        @foreach($_current_region->cities as $city)
                                            <option value="{{$city->id}}" {{$city->id == ($filter->array['city_id'] ?? 0) ? 'selected' : ''}}>{{$city->name}}</option>

                                        @endforeach
                                    @endif
                                </select>
                            </div>


                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="email_confirm" value="1" {{($filter->array['email_confirm'] ?? false) ? 'checked' : ''}}>Подтвержден Email
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="phone_confirm" value="1" {{($filter->array['phone_confirm'] ?? false) ? 'checked' : ''}}>Подтвержден Телефон
                            </label>
                        </div>

                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button type="button" class="btn btn-info" id="show_user_filter">Показать
                                </button>
                                <button type="submit" class="btn btn-primary">Сохранить
                                    фильтрацию
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <hr>
            <div class="table-responsive">
                <table class="table" width="100%" id="filtered_table">
                    <thead>
                    <th>Пользователь</th>
                    <th>Email</th>
                    <th class="text-center"><i class="fa fa-cog"></i></th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        var users_table_url = '/mailings?get_users=1';
        var emails_table = $('#filtered_table').DataTable({
            ajax: '{{route('filters.show', $filter->id)}}',
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

            ]

        })
        $(document).on('click', '#show_user_filter', function () {
            var $form = $('#user_filter').serialize()
            emails_table.ajax.url(users_table_url + '&' + $form).load()

        })
    });
</script>