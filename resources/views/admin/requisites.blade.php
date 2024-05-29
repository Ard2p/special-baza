<div class="container">
    <h3>Реквизиты</h3>

    <div class="col-md-6">
        @if($user->getActiveRequisiteType('contractor') === 'entity' || !$requisite_contractor)
            <div class="col-md-12">
                <b>Реквизиты исполнителя</b>
                <a href="#" class="btn btn-primary" data-toggle="modal"
                   data-target="#entityModal">Юридическое лицо</a>

                @if($requisite_contractor)
                    <a href="#"
                       class="btn btn-danger {{$user->getActiveRequisiteType('contractor') === 'individual' ? 'deleteIndividual' : 'deleteEntity'}}"
                       data-id="{{$requisite_contractor->id}}" data-type="contractor">Удалить
                        реквизиты {{$user->getActiveRequisiteType('contractor') === 'entity' ? 'ЮЛ' : 'ФЛ'}}</a>
                @endif
            </div>
            <hr>
        @endif
        @if($user->checkRole('customer'))

            <div class="col-md-12">
                <b>Реквизиты заказчика</b>
                @if($user->getActiveRequisiteType('customer') === 'individual' || !$requisite_customer)
                    <a href="#individual" class="btn btn-primary" data-toggle="modal"
                       data-target="#individualModal">Физическое лицо</a>
                @endif
                @if($user->getActiveRequisiteType('customer') === 'entity' || !$requisite_customer)
                    <a href="#" class="btn btn-primary" data-toggle="modal"
                       data-target="#entityIndividualModal">Юридическое лицо</a>
                @endif
                @if($requisite_customer)

                    <a href="#"
                       class="btn btn-danger {{$user->getActiveRequisiteType('customer') === 'individual' ? 'deleteIndividual' : 'deleteEntity'}}"
                       data-id="{{$requisite_customer->id}}" data-type="customer">Удалить
                        реквизиты {{$user->getActiveRequisiteType('customer') === 'entity' ? 'ЮЛ' : 'ФЛ'}}</a>
                @endif
            </div>

        @endif


    </div>
</div>
@push('footer-scripts')
    <link href="/css/jquery.datetimepicker.min.css" rel="stylesheet">
    <script src="/js/jquery.datetimepicker.full.min.js"></script>
    <script src="/js/calendar/datepicker.js"></script>
    <script>
        $.datetimepicker.setLocale('ru')
        jQuery('[data-toggle="datepicker"]').datetimepicker({
            format: 'Y/m/d',
            dayOfWeekStart: 1,
            timepicker: false
        });
        /*function showErrors(response) {
            var errors = '';
            var data = response.responseJSON;
            for (key in data) {
                errors += data[key] + '<br>';
            }
            swal(errors);
        }*/
        function showMessage(message) {
            swal(message);
        }

        function showErrors(d) {
            $('.form-element-errors').remove();
            $('.error').each(function () {
                $(this).removeClass('error');
            })

            var d = d.responseJSON
            if ('modals' in d) {
                modalError(d)
            }
            for (key in d) {
                var u = d[key]
                var ms = '';
                var input = $('body').find('[name="' + key + '"]');
                var par = input.closest('.form-group');
                var s = input.closest('.roller-item');

                par.addClass('error')
                s.addClass('error')

                for (k in u) {
                    ms += '<li>' + u[k] + '</li>';
                }

                console.log(ms);
                if (input.attr('type') !== 'checkbox' && input.attr('type') !== 'radio') {
                    $(' <ul class="form-element-errors">' + ms + '</ul>').insertAfter(input);
                } else {
                    input.parent().parent().append('<ul class="form-element-errors">' + ms + '</ul>')
                }
            }
        }

        $(document).ready(function () {
            $('#entityForm, #entityIndividualForm').on('submit', function (e) {
                e.preventDefault();
                var form = $(this)
                $.ajax({
                    url: '/entity_requisites',
                    type: 'POST',
                    data: form.serialize(),
                    success: function (data) {
                        setTimeout(function () {
                            location.reload()
                        }, 2000)
                        showMessage(data.message);

                    },
                    error: function (message) {
                        showMessage('Неверно заполнены поля.');
                        showErrors(message)
                    }
                })
            })

            $('#individualForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '/individual_requisites',
                    type: 'POST',
                    data: $('#individualForm').serialize(),
                    success: function (data) {
                        showMessage(data.message);
                        setTimeout(function () {
                            location.reload()
                        }, 2000)
                    },
                    error: function (message) {
                        showMessage('Неверно заполнены поля.');
                        showErrors(message)
                    }
                })
            })
            $(".fancybox").fancybox({
                openEffect: "none",
                closeEffect: "none"
            });

        });


        $(document).on('click', '.deleteEntity', function (e) {
            e.preventDefault()
            var id = $(this).data('id')
            var type = $(this).data('type');
            $.ajax({
                url: '/entity_requisites/' + id,
                type: 'DELETE',
                data: {_token: '{{csrf_token()}}', user_id: '{{$user->id}}', req_type: type},
                success: function (data) {
                    setTimeout(function () {
                        location.reload()
                    }, 2000)
                    showMessage(data.message);

                },
                error: function (message) {
                    showModalErrors(message)
                }
            })
        })

        $(document).on('click', '.deleteIndividual', function (e) {
            e.preventDefault()
            var id = $(this).data('id');
            $.ajax({
                url: '/individual_requisites/' + id,
                type: 'DELETE',
                data: {_token: '{{csrf_token()}}', user_id: '{{$user->id}}'},
                success: function (data) {
                    setTimeout(function () {
                        location.reload()
                    }, 2000)
                    showMessage(data.message);

                },
                error: function (message) {
                    showModalErrors(message)
                }
            })
        })
    </script>
@endpush
@include('admin.requisite_modals.create-entity')
@include('admin.requisite_modals.create-individual')
@include('admin.requisite_modals.create-entity-individual')