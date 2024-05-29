<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#report" aria-controls="report" role="tab" data-toggle="tab">
                    Рассылки
                </a></li>
        </ul>
        <div class="tab-content">
          {{--  <div role="tabpanel" id="users" class="tab-pane in active">
                <div class="panel">
                    <div class="panel-body">
                        <form class="row" id="user_filter">
                            <div class="col-md-12">

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
                                                data-url="https://office.transbaza.com/machineries/dependent-select/region_id/3"
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
                                            <button type="button" class="btn btn-primary"
                                                    data-url="{{route('filters.store')}}" id="save_filtration">Сохранить
                                                фильтрацию
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                                    data-target="#add_to_list">Добавить в список
                                            </button>
                                            <button type="button" class="btn btn-success" data-toggle="modal"
                                                    data-target="#create_list">Создать список
                                            </button>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <hr>

                    </div>
                </div>
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
            </div>--}}
           {{-- <div role="tabpanel" id="email" class="tab-pane">
                <div class="panel">
                    <div class="panel-body">

                        <div class="col-md-12">
                            <form id="show_emails_list">
                                <div class="col-md-6">
                                    <div class="form-group form-element-dependentselect">
                                        <label class="control-label">Список</label>
                                        <select style="width:100%;" class="form-control input-select column-filter"
                                                name="list_email_id">
                                            <option value="0">Выберите список...</option>
                                            @foreach($lists->where('type', 'email') as $list)
                                                <option value="{{$list->id}}">{{$list->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info" id="show_emails_filter">Показать
                                    </button>
                                    <button type="button" class="btn btn-warning" id="export_emails">Экспорт
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr>

                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" width="100%" id="emails_table">
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
            <div role="tabpanel" id="phone" class="tab-pane">
                <div class="panel">
                    <div class="panel-body">

                        <div class="col-md-12">
                            <form id="show_phones_list">
                                <div class="col-md-6">
                                    <div class="form-group form-element-dependentselect">
                                        <label class="control-label">Список</label>
                                        <select style="width:100%;" class="form-control input-select column-filter"
                                                name="list_phone_id">
                                            <option value="0">Выберите список...</option>
                                            @foreach($lists->where('type', 'phone') as $list)
                                                <option value="{{$list->id}}">{{$list->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info" id="show_phones_filter">Показать
                                    </button>
                                    <button type="button" class="btn btn-warning" id="export_phones">Экспорт
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr>

                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" width="100%" id="phones_table">
                        <thead>
                        <th>Пользователь</th>
                        <th>Телефон</th>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>--}}
            <div role="tabpanel" id="report" class="tab-pane  in active">
                <div class="panel">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-info" data-toggle="modal"
                                    data-target="#create_mailing">Создать рассылку
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" id="mailing_table" width="100%">
                        <thead>
                        <th>Наименование</th>
                        <th>Тип</th>
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
                        <label>Наименование списка</label>
                        <input class="form-control" type="text" name="name">
                    </div>
                    <div class="form-group">
                        <label class="radio-inline">
                            <input type="radio" name="type" value="phone" checked>Телефоны
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="type" value="email">Emails
                        </label>
                    </div>
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
                            <button type="button" class="btn btn-warning example_import" data-type="email">Шаблнон
                                импорта Email
                            </button>
                            <button type="button" class="btn btn-warning example_import" data-type="phone">Шаблнон
                                импорта Телефонов
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="ids">
                </form>
            </div>

            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-primary" id="create_list_name_btn">Добавить</button>
                <button type="button" class="btn btn-custom" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>


<div class="modal" id="create_mailing" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Создать список</h4>
            </div>
            <div class="modal-body">
                <form id="create_mailing_form" action="{{route('mailings.store')}}">
                    @csrf
                    <div class="form-group">
                        <label>Наименование рассылки</label>
                        <input class="form-control" type="text" name="name">
                    </div>
                    <div class="form-group">
                        <label class="radio-inline">
                            <input type="radio" name="mailing_type" value="phone">Телефоны
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="mailing_type" value="email">Emails
                        </label>
                    </div>
                    <div id="show_by_phone" style="display: none">
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Список</label>
                            <select style="width:100%;" class="form-control input-select"
                                    data-placeholder="Выберите список..." name="list_phone_id[]" multiple>
                                @foreach($lists->where('type', 'phone') as $list)
                                    <option value="{{$list->id}}">{{$list->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Выберите шаблон</label>
                            <select style="width:100%;" class="form-control input-select column-filter"
                                    data-placeholder="Выберите шаблон..." name="template_phone_id">
                                @foreach($templates->where('type', 'phone') as $template)
                                    <option value="{{$template->id}}">{{$template->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="show_by_email" style="display: none">
                        <div class="form-group">
                            <label>Тема Email</label>
                            <input class="form-control" type="text" name="subject">
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Список</label>
                            <select style="width:100%;" class="form-control input-select column-filter"
                                    data-placeholder="Выберите список..." name="list_email_id[]" multiple>
                                @foreach($lists->where('type', 'email') as $list)
                                    <option value="{{$list->id}}">{{$list->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Выберите шаблон</label>
                            <select style="width:100%;" class="form-control input-select column-filter"
                                    data-placeholder="Выберите шаблон..." name="template_email_id">
                                @foreach($templates->where('type', 'email') as $template)
                                    <option value="{{$template->id}}">{{$template->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="use_filter" value="1">Использовать поиск по фильтрам
                        </label>
                    </div>
                    <div class="form-group form-element-dependentselect" id="filters_select" style="display: none">
                        <label class="control-label">Список фильтров</label>
                        <select style="width:100%;" class="form-control input-select"
                                data-placeholder="Выберите список..." name="filter_id">
                            @foreach($filters as $list)
                                <option value="{{$list->id}}">{{$list->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-primary" id="create_mailing_btn">Добавить</button>
                <button type="button" class="btn btn-custom" data-dismiss="modal">Отмена</button>
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
                    <div class="form-group form-element-dependentselect">
                        <label class="control-label">Список</label>
                        <select style="width:100%;" class="form-control input-select column-filter"
                                data-placeholder="Выберите список" name="list_id">
                            @foreach($lists as $list)
                                <option value="{{$list->id}}">{{$list->name}}</option>
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
                            <button type="button" class="btn btn-warning example_import" data-type="email">Шаблнон
                                импорта Email
                            </button>
                            <button type="button" class="btn btn-warning example_import" data-type="phone">Шаблнон
                                импорта Телефонов
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
<form style="display: none" id="export_form" action="{{route('export_form')}}" method="POST">
    @csrf
    <input type="text" name="type" value="">
</form>
