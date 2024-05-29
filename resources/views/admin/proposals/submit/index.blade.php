<div class="content body">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#emails_list" aria-controls="all" role="tab"
                                                      data-toggle="tab">
                   Необходима консультация
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="emails_list" class="tab-pane in active">
                <div class="table-responsive">
                    <table class="table" id="email_list">
                        <thead>
                        <th>#ID</th>
                        <th>Пользователь</th>
                        <th>Комментарий</th>
                        <th>Дата</th>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        </thead>
                        <tbody>
                        @foreach($forms as $form)
                            <tr {!!  $form->proposal_id === 0 ? 'style="background: #dd4b3926;"': ''!!} >
                                <td>{{$form->id}}</td>
                                <td>{{$form->user->email}}</td>
                                <td>{{$form->comment}}</td>
                                <td>{{$form->created_at}}</td>
                                <td><a class="btn btn-primary"
                                       href="{{route('submit-proposal.edit', $form->id)}}"><i
                                                class="fa fa-info"></i>&nbsp; Просмотр</a>
                                    @if($form->id !== 1)
                                        <form action="{{route('submit-proposal.destroy', $form->id)}}"
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