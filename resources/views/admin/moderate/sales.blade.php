<div class="content body">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>

    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#email" aria-controls="all" role="tab" data-toggle="tab">
                   ОБЪЯВЛЕНИЯ О ПРОДАЖЕ
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="email" class="tab-pane in active">
                <div class="table-responsive">
                    <table class="table" id="html_list"  width="100%">
                        <thead>
                        <th>Наименование</th>
                        <th>Описание</th>
                        <th>Фото</th>
                        <th>Пользователь</th>
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

<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        var data_table = $('#html_list').DataTable({
            "ajax": '{{route('moderate_sales', ['get_sales' => 1])}}',
            language: data_table_lang,


            "columns": [
                {
                    "data": "name",
                },
                {
                    "data": "sale.description",
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<a href="/'+ full['photo'] +'" data-toggle="lightbox">'
                                + '<img src="/'+ full['photo'] +' " width="80px">'
                                + '</a>';
                        }
                        return data;
                    },
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<a target="_blank" href="/users/'+ full['user_id'] +'/edit" >' + full['user']['id_with_email']
                                + '</a>';
                        }
                        return data;
                    },
                },
                {
                    "data": "sale.moderate_button",
                },

            ],
        })
        $(document).on('click', '.set_moderate, .un_moderate', function (e) {
            e.preventDefault();
            let $btn = $(this);
            $.ajax({
                url: $btn.data('url'),
                type: 'POST',
                data: {_token:  document.head.querySelector('meta[name="csrf-token"]').content},
                success: function () {
                    data_table.ajax.reload();
                },
                error:function () {

                }
            })
        })
    });
</script>