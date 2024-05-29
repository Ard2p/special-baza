<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#failed" aria-controls="all" role="tab" data-toggle="tab">
                    Аудит действий
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="failed" class="tab-pane in active">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>Дата</th>
                        <th>Пользователь</th>
                        <th>Изменяемая модель</th>
                        <th>Действие</th>
                        <th>Старые значения</th>
                        <th>Новые значения</th>
                        <th>URL</th>
                        <th>IP</th>
                        </thead>
                        <tbody>
                        @foreach($audits as $item)
                            <tr class="module">
                                <td>{{$item->created_at}}</td>
                                <td>{{$item->user->id_with_email ?? ''}}</td>
                                <td>{{$item->auditable_type_name}}</td>
                                <td>{{$item->event_name}}</td>
                                <td>
                                    @if($item->old_values->isEmpty())
                                        Данные отсутствуют
                                    @else
                                        <button type="button" data-toggle="collapse" data-target="#stack{{{$item->id}}}"
                                                aria-expanded="false" aria-controls="stack{{{$item->id}}}"
                                                class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                                                data-display="stack{{{$item->id}}}">
                                            <span class="fa fa-search"></span>
                                        </button>
                                        <div class="collapse" aria-expanded="false" id="stack{{{$item->id}}}">

                                            <ul class="list-group">
                                                @foreach($item->old_values as $key => $value)
                                                    <li class="list-group-item">{{$key}} : {{$value}}</li>
                                                @endforeach
                                            </ul>

                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($item->new_values->isEmpty())
                                        Данные отсутствуют
                                    @else
                                        <button type="button" data-toggle="collapse" data-target="#new{{{$item->id}}}"
                                                aria-expanded="false" aria-controls="new{{{$item->id}}}"
                                                class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                                                data-display="new{{{$item->id}}}">
                                            <span class="fa fa-search"></span>
                                        </button>
                                        <div class="collapse" aria-expanded="false" id="new{{{$item->id}}}">

                                            <ul class="list-group">
                                                @foreach($item->new_values as $key => $value)
                                                    <li class="list-group-item">{{$key}} : {{$value}}</li>
                                                @endforeach
                                            </ul>

                                        </div>
                                    @endif
                                </td>
                                <td>{{$item->url}}</td>
                                <td>{{$item->ip_address}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
        var data_table = $('table').DataTable({
            "order": [0, 'desc'],
            language: data_table_lang,

        });
    });
</script>
<style>
    .module {
        width: 500px;
        font-size: 14px;
        line-height: 1.5;
    }

    .module div.collapse[aria-expanded="false"] {
        height: 42px !important;
        overflow: hidden;

        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .module div.collapsing[aria-expanded="false"] {
        height: 42px !important;
    }

    .module div.collapsed:after {
        content: '+ Show More';
    }

    .module a:not(.collapsed):after {
        content: '- Show Less';
    }
</style>