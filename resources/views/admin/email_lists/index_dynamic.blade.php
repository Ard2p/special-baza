<div class="content body">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
        var email_table_url = '{{route('mailing_list.index', ['get_list' => 1])}}'
        var phone_table_url = '{{route('mailing_list.index', ['get_phones' => 1])}}'
        var dynamic_email_table_url = '{!!  route('mailing_list.index', ['get_filters' => 1, 'type' => 'email'])!!}'
        var phone_dynamic_table_url = '{!!  route('mailing_list.index', ['get_filters' => 1, 'type' => 'phone'])!!}'
    </script>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#emails_list" aria-controls="all" role="tab" data-toggle="tab">
               Динамический список Эл. Писем
                </a></li>
        </ul>
        <div class="tab-content">

            <div role="tabpanel" id="emails_list" class="tab-pane in active">
                <div role="tabpanel" id="email_filters" class="tab-pane">
                    <a href="{{route('filters.create', ['type' => 'email'])}}" class="btn btn-primary">Новый динамический список</a>
                    <div class="table-responsive">
                        <table class="table" id="dynamic_emails">
                            <thead>
                            <th>#ID</th>
                            <th>Наименование</th>
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
</div>