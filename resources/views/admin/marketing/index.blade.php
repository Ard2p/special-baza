<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#email" aria-controls="all" role="tab" data-toggle="tab">
                    по Email
                </a></li>
            <li role="presentation"><a href="#phone" aria-controls="users" role="tab" data-toggle="tab">

                    по Телефону
                </a></li>
            <li role="presentation"><a href="#settings" aria-controls="users" role="tab" data-toggle="tab">
                    Настройки
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="email" class="tab-pane in active">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>Дата</th>
                        <th>Email</th>
                        <th>URL</th>
                        <th>Просмотрено</th>
                        <th>Дата просмотра</th>
                        <th>Клик</th>
                        <th>Дата клика</th>
                        </thead>
                        <tbody>
                        @foreach($list_email as $item)
                            <tr>
                                <td>{{$item->created_at->format('d.m.Y H:i')}}</td>
                                <td>{{$item->email}}</td>
                                <td>{{$item->url}}</td>
                                <td>{!!  $item->is_watch ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>' !!}</td>
                                <td>{{$item->watch_at }}</td>

                                <td>
                                    @switch($item->confirm_status)
                                        @case(0)
                                        @break
                                        @case(1)
                                        Да
                                        @break
                                        @case(2)
                                        Нет
                                        @break
                                    @endswitch

                                </td>
                                <td>{{$item->confirm_at }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="phone" class="tab-pane">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>Дата</th>
                        <th>Телефон</th>
                        <th>URL</th>
                        <th>Доставлено</th>
                        <th>Клик</th>
                        <th>Дата клика</th>
                        </thead>
                        <tbody>
                        @foreach($list_phone as $item)
                            <tr>
                                <td>{{$item->created_at->format('d.m.Y H:i')}}</td>
                                <td>{{$item->phone_format}}</td>
                                <td>{{$item->url}}</td>
                                <td>{!!  $item->is_watch ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>' !!}</td>
                                <td>
                                    @switch($item->confirm_status)
                                        @case(0)
                                        @break
                                        @case(1)
                                        Да
                                        @break
                                        @case(2)
                                        Нет
                                        @break
                                    @endswitch

                                </td>
                                <td>{{$item->confirm_at }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="settings" class="tab-pane ">

                <div class="panel panel-default">
                    <form id="settings_form" action="{{route('admin-share.store')}}">
                        @csrf
                        <div class="form-elements panel-body">
                            <div class="col-md-6">

                                <div class="form-group form-element-checkbox ">
                                    <div class="checkbox"><label>
                                            <input name="send_share_by_phone"
                                                   type="checkbox"
                                                   value="1" {{$options->where('key', 'send_share_by_phone')->first()->value == '1' ? 'checked' : '' }}>
                                            Отправлять ссылку на телефон
                                        </label></div>
                                </div>
                                <div class="form-group form-element-checkbox ">
                                    <div class="checkbox"><label>
                                            <input name="send_share_by_email"
                                                   type="checkbox"
                                                   value="1" {{$options->where('key', 'send_share_by_email')->first()->value == '1' ? 'checked' : '' }}>
                                            Отправлять ссылку на Email
                                        </label></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-buttons panel-footer panel-footer">
                            <div role="group" class="btn-group">
                                <button type="button" id="save_share_settings" class="btn btn-primary">
                                    <i class="fa fa-check"></i> Сохранить
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

