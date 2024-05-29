<style>
    .click_link:hover {
        cursor: pointer;
        background: #0f74a8;
    }
</style>
<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#email" aria-controls="all" role="tab" data-toggle="tab">
                    Email друзей
                </a></li>
            <li role="presentation"><a href="#phone" aria-controls="users" role="tab" data-toggle="tab">
                    Телефоны друзей
                </a></li>
            <li role="presentation"><a href="#report" aria-controls="report" role="tab" data-toggle="tab">
                    Отчет
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="email" class="tab-pane in active">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>Пользователь</th>
                        <th>Email</th>
                        <th>Примечание</th>
                        <th>Кол-во отправленых ссылок</th>
                        <th>Успешно отправленых</th>
                        </thead>
                        <tbody>
                        @foreach($list_email as $item)
                            <tr>
                                <td>{{$item->user->id_with_email}}</td>
                                <td>{{$item->email}}</td>
                                <td>{{$item->name}}</td>
                                <td>{{$item->email_links->count()}}</td>
                                <td>{{$item->email_links_count}}</td>

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
                        <th>Пользователь</th>
                        <th>Телефон</th>
                        <th>Примечание</th>
                        <th>Кол-во отправленых ссылок</th>
                        <th>Успешно отправленых</th>
                        </thead>
                        <tbody>
                        @foreach($list_phone as $item)
                            <tr>
                                <td>{{$item->user->id_with_email}}</td>
                                <td>{{$item->phone_format}}</td>
                                <td>{{$item->name}}</td>
                                <td>{{$item->sms_links->count()}}</td>
                                <td>{{$item->sms_links_count}}</td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="report" class="tab-pane">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>URL</th>
                        <th>Кол-во отправлений</th>
                        <th>Успешных кликов</th>
                        </thead>
                        <tbody>
                        @foreach($collection as $link => $item)
                            <tr>
                                <td>{{$link}}</td>
                                <td><a data-url="{{route('link_info', ['link' => urlencode($link), 'type' => 'all'])}}" class="click_link"

                                       data-toggle="modal" data-target="#show_info">{{$item->count()}}</a></td>

                                <td><a data-target="#show_info"  data-toggle="modal" data-url="{{route('link_info', ['link' => urlencode($link), 'type' => 'success'])}}" class="click_link">{{$item->success}}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="show_info" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Информация</h4>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer button two-btn">
                <button type="button" class="btn-custom" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>