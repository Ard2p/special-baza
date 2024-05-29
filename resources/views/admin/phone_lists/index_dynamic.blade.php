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
            <li role="presentation" class="active"><a href="#phones_list" aria-controls="all" role="tab" data-toggle="tab">
                   Динамический список телефонов
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="phones_list" class="tab-pane in active">
                <a href="{{route('filters.create', ['type' => 'phone'])}}" class="btn btn-primary">Новый динамический список</a>

                <div class="table-responsive">
                    <table class="table" id="dynamic_phones">
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