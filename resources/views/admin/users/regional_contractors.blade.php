<div class="panel panel-default">

    <div class="panel-heading">
        <b>Региональный представитель {{$user->id_with_email}}</b>
        <p></p>
        <a href="#" data-toggle="modal"
           data-target="#addContractModal" class="btn btn-primary"><i class="fa fa-plus"></i>Добавить
            исполнителя
        </a>
        <div class="pull-right"></div>
    </div>
    <table class="table table-striped" id="my_contractors" width="100%">
        <thead>
        <tr>
            <th class="row-header">
                Email

            </th>
            <th class="row-header">
                Телефон

            </th>
            <th class="row-header">
                Регион
            </th>
            <th class="row-header">
                Город
            </th>
            <th class="row-header">
               Действие
            </th>
        </tr>
        </thead>
        <tbody>

        </tbody>
        <tr></tr>
        </tfoot>
    </table>
    <div class="panel-footer"></div>
</div>

<script>
    var array = [];
    $(document).ready(function () {
        var lang = {!!  json_encode(trans('sleeping_owl::lang.table'))!!}
        var all_contractors = $('#all_contractors').DataTable({
                "language": lang,
                "ajax": "/regional/{{$user->id}}?all_contractors",
                select: {
                    style: 'multi',
                    selector: 'td:first-child'
                },
                "columns": [
                    {"data": "email",},
                    {"data": "phone",},
                    {"data": "region_name",},
                    {"data": "city_name",},
                    {
                        sDefaultContent: "",
                        "render": function (data, type, full, meta) {
                            if (type === 'display') {
                                array[full['id']] = full;
                                data = '<button class="btn btn-success addContractor" data-rep="' + full['regional_representative_id'] + '" data-id="' + full['id'] + '">Добавить исполнителя</button>';
                            }
                            return data;
                        },
                    },
                ]
            });

        var my_contractors = $('#my_contractors').DataTable({
            "ajax": "/regional/{{$user->id}}?regional_contractors",
            "language": lang,
            "columns": [
                {"data": "email",},
                {"data": "phone",},
                {"data": "region_name",},
                {"data": "city_name",},
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            data = '<button class="btn btn-warning deleteContractor" data-id="' + full['id'] + '">Убрать исполнителя</button>';
                        }
                        return data;
                    },
                },
            ]
        });

        function showErrors(response) {
            var errors = '';
            var data = response.responseJSON;
            for (key in data) {
                errors += data[key] + '<br>';
            }
            swal(errors);
        }

        $(document).on('click', '.addContractor', function (e) {

            var id = $(this).data('id');
            var rep = $(this).data('rep');
            if (rep !== 0) {
                var current = array[id]
                var regional = array[id]['regional_representative']
                swal({
                    title: 'Внимание!',
                    text: "у Исполнителя "+ current['email'] +" уже имеется РП "+ regional['email'] +". СМЕНИТЬ У ИСПОЛНИТЕЛЯ ПРЕЖНЕГО РП НА НОВОГО РП {{$user->email}} ?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Да, сменить!',
                    cancelButtonText: 'Нет, отмена!'
                }).then((result) => {
                    if (result.value) {
                        addContractor(id)
                    }
                })
            } else {
                addContractor(id)
            }
        })

        function addContractor(id)
        {
            $.ajax({
                url: '{{route('regional.store')}}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    id: id,
                    current_id: '{{$user->id}}'
                },
                success: function (e) {
                    swal(e.message)
                    all_contractors.ajax.reload()
                    my_contractors.ajax.reload()
                },
                error: function (e) {
                    showErrors(e)
                }
            })
        }
        $(document).on('click', '.deleteContractor', function (e) {
            var id = $(this).data('id')
            $.ajax({
                url: '/regional/' + id,
                type: 'DELETE',
                data: {
                    _token: '{{csrf_token()}}'
                },
                success: function (e) {
                    swal(e.message)
                    all_contractors.ajax.reload()
                    my_contractors.ajax.reload()
                },
                error: function (e) {
                    showErrors(e)
                }
            })
        })
    })
</script>

<div class="modal fade" id="addContractModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Добавить исполнителя</h4>
            </div>
            <div class="modal-body">
                <div class="panel panel-default">

                    <div class="panel-heading">
                    </div>
                    <table class="table table-striped" id="all_contractors" width="100%">
                        <thead>
                        <tr>
                            <th class="row-header">
                            </th>
                            <th class="row-header">
                                Email

                            </th>
                            <th class="row-header">
                                Телефон

                            </th>
                            <th class="row-header">
                                Регион
                            </th>
                            <th class="row-header">
                                Город
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer button two-btn">
            <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
        </div>
    </div>
</div>