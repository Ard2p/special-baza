<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    <h4>АнтиСпам фильтры</h4>
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#transactions" aria-controls="transactions" role="tab"
                                                      data-toggle="tab">
                    Пользователи
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="transactions" class="tab-pane in active">

                    <form id="check_spam_users" class="row" action="{{route('spam_system')}}">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button class="btn btn-primary">Проверить пользователей</button>
                            </div>
                        </div>
                    </form>
                <div class="table-responsive">
                    <table class="table" id="spam_table">
                        <thead>
                        <th>Пользователь</th>
                        <th>Дата проверки</th>
                        <th>Система</th>
                        <th>Статус</th>
                        <th>Действия</th>
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
        let token = document.head.querySelector('meta[name="csrf-token"]').content;
        var users_list = $('#spam_table').DataTable({
            "ajax": '{{route('spam_system', ['get_users' => 1])}}',
            language: data_table_lang,


            "columns": [
                {
                    "data": "email",
                },
                {
                    "data": "updated_at",
                },
                {
                    "data": "spam_system",
                },
                {
                    "data": "status",
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            if(full['blocked']){
                                data = '<button type="button" class="btn btn-primary btn-sm unblock_user" data-url="' + full['unblock_url'] + '">Разблокировать</button>&nbsp;';
                            }else {
                                data = '<button type="button" class="btn btn-danger btn-sm block_user" data-url="' + full['block_url'] + '">Заблокировать</button>&nbsp;';
                            }


                        }
                        return data;
                    },
                },

            ],
        })

        $(document).on('submit', '#check_spam_users', function (e) {
            e.preventDefault();
            let $form = $(this);
            swal({
                title: 'Идет проверка...',
                onBeforeOpen: () => {
                    swal.showLoading()
                },
            })
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function (e) {
                    swal.close()
                    swal('Проверка завершена.')
                    users_list.ajax.reload()
                },
                error: function (e) {
                    swal.close()
                    swal('Ошибка.')
                }

            })
        })
        $(document).on('click', '.unblock_user', function (e) {
            e.preventDefault()
            let $button = $(this);
            swal({
                title: 'Внимание!',
                text: "Разблокировать пользователя?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Да!',
                cancelButtonText: 'Нет, отмена!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: $button.data('url'),
                        type: 'POST',
                        data: {_token: token},
                        success: function (e) {
                            users_list.ajax.reload()
                        }
                    })
                }
            })

        })

        $(document).on('click', '.block_user', function (e) {
            e.preventDefault()
            let $button = $(this);
            swal({
                title: 'Внимание!',
                text: "Заблокировать пользователя?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Да!',
                cancelButtonText: 'Нет, отмена!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: $button.data('url'),
                        type: 'POST',
                        data: {_token: token},
                        success: function (e) {
                            users_list.ajax.reload()
                        }
                    })
                }
            })

        })
    });
</script>
