<button class="btn btn-primary" data-toggle="modal" data-target="#create_equipment">Новое оборудование</button>
<div class="table-responsive">
    <table class="table table-striped" id="equipment_table">
        <thead>
        <tr>
            <th>#ID</th>
            <th>Наименование</th>
            <th><i class="fa fa-cog"></i></th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
</div>

<div class="modal" id="create_equipment" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Добавить оборудование</h4>
            </div>
            <form class="modal-body" action="{{route('equipment.store')}}" id="equipmentForm">
                @csrf
                <div class="form-group">
                    <label>Наименование</label>
                    <input type="text" id="name_field" class="form-control" name="name">
                </div>
                <input name="type_id" type="hidden" value="{{$category_id}}">
            </form>
            <div class="modal-footer button two-btn">
                <button type="button" class="btn btn-primary" id="addEquipment">Добавить</button>
                <button type="button" class="btn btn-custom" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<script>
    var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    document.addEventListener("DOMContentLoaded", function (event) {
        var data_table_url = '{{route('equipment.index', ['category_id' => $category_id])}}';
        var data_table = $('#equipment_table').DataTable({
            language: data_table_lang,
            ajax: data_table_url,
            "autoWidth": false,
            "ordering": false,
            "columns": [

                {
                    "data": "id",
                },

                {
                    "data": "name",
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<a href="'+ full['edit_url'] +'" class="btn btn-xs btn-primary" title="Редактировать" data-toggle="tooltip">\n' +
                                '            <i class="fa fa-pencil"></i>\n' +
                                '    \n' +
                                '    </a>' + full['delete_form'];

                        }
                        return data;
                    },
                },

            ]
        });

        $(document).on('click','#addEquipment', function () {
            let $form = $('#equipmentForm');
            $.ajax({
                url: $form.attr('action'),
                data: $form.serialize(),
                type: 'POST',
                success: function (e) {
                    swal(e.message)
                    $form.find('#name_field').val('')
                    data_table.ajax.reload()
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }
            })
        })

    })
</script>