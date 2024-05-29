<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#failed" aria-controls="all" role="tab" data-toggle="tab">
                    Упавшие задачи
                </a></li>
            <li role="presentation"><a href="#in_progress" aria-controls="users" role="tab" data-toggle="tab">
                    Задачи в очереди
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="failed" class="tab-pane in active">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>Дата</th>
                        <th>Исключение</th>
                        <th>Payload</th>
                        </thead>
                        <tbody>
                        @foreach($failed as $item)
                            <tr class="module">
                                <td>{{$item->failed_at}}</td>
                                <td>
                                    <button type="button"  data-toggle="collapse" data-target="#stack{{{$item->id}}}"
                                            aria-expanded="false" aria-controls="stack{{{$item->id}}}"
                                            class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                                            data-display="stack{{{$item->id}}}">
                                        <span class="fa fa-search"></span>
                                    </button>
                                    <div class="collapse" aria-expanded="false" id="stack{{{$item->id}}}" >{{$item->exception}}
                                    </div>
                                  </td>
                                <td>
                                    <button type="button"  data-toggle="collapse" data-target="#payload{{{$item->id}}}"
                                            aria-expanded="false" aria-controls="stack{{{$item->id}}}"
                                            class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                                            data-display="payload{{{$item->id}}}">
                                        <span class="fa fa-search"></span>
                                    </button>
                                    <div class="collapse" aria-expanded="false" id="payload{{{$item->id}}}"
                                         style="min-height: 42px !important;">{{$item->payload}}
                                    </div>
                                    </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="in_progress" class="tab-pane">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th>Дата</th>
                        <th>Payload</th>
                        </thead>
                        <tbody>
                        @foreach($queue as $item)
                            <tr class="module">
                                <td>{{$item->created_at}}</td>
                                <td>
                                    <button type="button"  data-toggle="collapse" data-target="#queue{{{$item->id}}}"
                                            aria-expanded="false" aria-controls="stack{{{$item->id}}}"
                                            class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                                            data-display="queue{{{$item->id}}}">
                                        <span class="fa fa-search"></span>
                                    </button>
                                    <div class="collapse" aria-expanded="false" id="queue{{{$item->id}}}">{{$item->payload}}
                                    </div>
                                </td>
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

    .module div.collapsed:after  {
        content: '+ Show More';
    }

    .module a:not(.collapsed):after {
        content: '- Show Less';
    }
</style>