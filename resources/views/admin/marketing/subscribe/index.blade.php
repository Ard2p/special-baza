<div class="content body">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#emails_list" aria-controls="all" role="tab"
                                                      data-toggle="tab">
                    Подписки
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="emails_list" class="tab-pane in active">
                <a href="{{route('subscribe.create')}}" class="btn btn-primary">Новая
                    подписка</a>
                <div class="table-responsive">
                    <table class="table" id="email_list">
                        <thead>
                        <th>#ID</th>
                        <th>Наименование</th>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        </thead>
                        <tbody>
                        @php
                            $alias_array = ['news', 'article', 'system'];
                        @endphp
                        @foreach($subscribes as $subscribe)

                            <tr>
                                <td>{{$subscribe->id}}</td>
                                <td>{{$subscribe->name}}</td>
                                <td><a class="btn btn-light" href="{{route('subscribe.show', $subscribe->id)}}"><i
                                                class="fa fa-cogs"></i> </a>
                                    @if(!in_array($subscribe->alias, $alias_array))
                                        <form action="{{route('subscribe.destroy', $subscribe->id)}}"
                                              method="POST"
                                              style="display:inline-block;">
                                            @csrf
                                            <input
                                                    type="hidden" name="_method" value="delete">
                                            <button class="btn btn-xs btn-danger btn-delete" title="Удалить"
                                                    data-toggle="tooltip"><i class="fa fa-trash"></i></button>
                                        </form>
                                    @endif
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