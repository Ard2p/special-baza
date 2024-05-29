<div class="content body">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#emails_list" aria-controls="all" role="tab"
                                                      data-toggle="tab">
                    Контактная форма
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="emails_list" class="tab-pane in active">
                <a href="{{route('contact-form.create')}}" class="btn btn-primary">Новая
                    форма</a>
                <div class="table-responsive">
                    <table class="table" id="email_list">
                        <thead>
                        <th>#ID</th>
                        <th>Наименование</th>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        </thead>
                        <tbody>
                        @foreach($forms as $form)
                            <tr>
                                <td>{{$form->id}}</td>
                                <td>{{$form->name}}</td>
                                <td><a class="btn btn-primary btn-xs" href="{{route('contact-form.edit', $form->id)}}"><i
                                                class="fa fa-pencil"></i> </a>

                                    <form action="{{route('contact-form.destroy', $form->id)}}"
                                          method="POST"
                                          style="display:inline-block;">
                                        @csrf
                                        <input
                                                type="hidden" name="_method" value="delete">
                                        <button class="btn btn-xs btn-danger btn-delete" title="Удалить"
                                                data-toggle="tooltip"><i class="fa fa-trash"></i></button>
                                    </form>

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