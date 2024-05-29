<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#seo" aria-controls="all" role="tab" data-toggle="tab">
                    Seo страниц
                </a></li>
            <li role="presentation"><a href="#settings" aria-controls="users" role="tab" data-toggle="tab">
                    Настройки
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="seo" class="tab-pane in active">
                <div class="panel">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="btn-group">
                                    <a href="{{route('seo_blocks.create')}}" class="btn btn-primary">Добавить
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" id="seo_table">
                        <thead>
                        {{--   <th><input type="checkbox" class="selectAll"></th>--}}
                        <th>URL</th>
                        <th>Примечание</th>
                        <th>Опубликовано</th>
                        <th>Действие</th>
                        </thead>
                        <tbody>
                        @foreach($blocks as $block)
                              <tr>
                                  <td>{{$block->url}}</td>
                                  <td>{{$block->comment}}</td>
                                  <td>{!!$block->is_active ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>'  !!}</td>
                                  <td><a href="{{route('seo_blocks.edit', $block->id)}}" class="btn btn-xs btn-primary" title="Редактировать" data-toggle="tooltip">
                                          <i class="fa fa-pencil"></i>

                                      </a>

                                      <form action="{{route('seo_blocks.destroy', $block->id)}}" method="POST" style="display:inline-block;">
                                          <input type="hidden" name="_token" value="{{csrf_token()}}">
                                          <input type="hidden" name="_method" value="delete">
                                          <button class="btn btn-xs btn-danger btn-delete" title="Удалить" data-toggle="tooltip">
                                              <i class="fa fa-trash"></i>
                                          </button>
                                      </form>

                                  </td>
                              </tr>
                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="settings" class="tab-pane">
            </div>
        </div>
    </div>
</div>
