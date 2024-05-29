<div class="content body">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>

    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#phone" aria-controls="all" role="tab" data-toggle="tab">
                    TXT
                </a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="phone" class="tab-pane in active">
                <button type="button" class="btn btn-primary" data-toggle="modal"
                        data-target="#create_template">Создать шаблон
                </button>
                <div class="table-responsive">
                    <table class="table" id="txt_list" width="100%">
                        <thead>
                        <th>Наименование</th>
                        <th>Тип</th>
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

<div class="modal" id="preview_iframe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Превью</h4>
            </div>
            <div class="modal-body" id="__iframe">

            </div>

            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-custom" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="edit_template" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Редактировать шаблон</h4>
            </div>
            <div class="modal-body" id="template_form_edit">

            </div>
            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-info" id="save_edit_template">Сохранить</button>
                <button type="button" class="btn btn-custom" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="create_template" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Создать список</h4>
            </div>
            <div class="modal-body">
                <form id="create_template_form" action="{{route('templates.store')}}">
                    @csrf
                    <div class="form-group">
                        <label>Наименование шаблона</label>
                        <input class="form-control" type="text" name="name">
                    </div>
                    <input type="hidden" name="type" value="phone">
                    <div class="form-group">
                        <label>Текст</label>
                        <textarea class="form-control" type="text" name="text"></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-primary" id="create_template_btn">Добавить</button>
                <button type="button" class="btn btn-custom" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {

        var txt_list = $('#txt_list').DataTable({
            "ajax": '{{route('tpl_txt', ['get_phone' => 1])}}',
            language: data_table_lang,


            "columns": [
                {
                    "data": "name",
                },
                {
                    "data": "type_name",
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<button type="button" class="btn btn-primary btn-sm edit_template" data-toggle="modal"  data-target="#edit_template" data-url="' + full['update_link'] + '"><i class="fa fa-pencil"></i></button>&nbsp;';
                            data += '<button type="button" class="btn btn-info btn-sm preview_template" data-toggle="modal"   data-target="#preview_iframe" data-id="' + full['id'] + '"><i class="fa fa-eye"></i></button>';

                        }
                        return data;
                    },
                },

            ],
        })
        $(document).on('click', '.preview_template', function () {
            iframe($(this).data('id'))
        })

        function iframe(id) {
            $('#__iframe').html('')
            var link = "/get-template/" + id;
            var _iframe = document.createElement('iframe');
            _iframe.width = "100%";
            _iframe.scrolling = "yes";
            _iframe.style.minHeight = "100%";
            _iframe.setAttribute("src", link);
            document.getElementById("__iframe").appendChild(_iframe);
        }


        $(document).on('click', '#save_edit_template', function () {
            var $form = $('#edit_template_form');
            $.ajax({
                url: $form.attr('action'),
                type: 'PATCH',
                data: $form.serialize(),
                success: function (e) {
                    refreshTables()
                    $('.modal').modal('hide')
                    swal(e.message)
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }
            })
        })
        $(document).on('click', '.edit_template', function () {
            var url = $(this).data('url');
            $('#template_form_edit').html('')
            $.ajax({
                url: url,
                type: 'GET',
                success: function (e) {
                    $('#template_form_edit').html(e.data)

                },
                error: function () {

                }
            })
        })
        $(document).on('click', '#create_template_btn', function () {
            var $form = $('#create_template_form')
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function (e) {
                    refreshTables()
                    swal(e.message)
                    $form[0].reset()
                    $('.modal').modal('hide');
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }

            })
        })

        function refreshTables() {
            html_list.ajax.reload();
            txt_list.ajax.reload();
        }
    });
</script>