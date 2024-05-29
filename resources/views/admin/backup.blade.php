<div class="panel">
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-12 clearfix">
                <a id="create-new-backup-button" href="{{ url('backup/create') }}" class="btn btn-primary pull-right"
                   style="margin-bottom:2em;"><i
                            class="fa fa-plus"></i> Новый бекап
                </a>
            </div>
            <div class="col-xs-12">
                @if (count($backups))

                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Файл</th>
                            <th>Размер</th>
                            <th>Дата</th>
                            <th>Срок</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($backups as $backup)
                            <tr>
                                <td>{{ $backup['file_name'] }}</td>
                                <td>{{ humanFilesize($backup['file_size']) }}</td>
                                <td>

                                    {{ \Carbon\Carbon::createFromTimestamp($backup['last_modified']) }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::createFromTimestamp($backup['last_modified'])->diffForHumans(now()) }}
                                </td>
                                <td class="text-right">
                                    <a class="btn btn-xs btn-default"
                                       href="{{ url('backup/download/'.$backup['file_name']) }}"><i
                                                class="fa fa-cloud-download"></i> Загрузить</a>
                                    <a class="btn btn-xs btn-danger" data-button-type="delete"
                                       href="{{ url('backup/delete/'.$backup['file_name']) }}"><i
                                                class="fa fa-trash-o"></i>
                                        Удалить</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="well">
                        <h4>Резервных копий нет</h4>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>