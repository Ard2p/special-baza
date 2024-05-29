<div class="panel">
    <div class="panel-body" id="_panel">
        <div id="calendar"></div>
    </div>
</div>
<div class="modal modal-fade" id="proposal-modal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                            class="sr-only">Close</span></button>
                <h4 class="modal-title">
                    Информация о заказе
                </h4>
            </div>
            <div class="modal-body">
                <div class="machines-popup-wrap">

                    <div class="item-machine-popup">
                        <div class="image">

                        </div>
                        <div class="content">
                            <p>
                                <strong id="_customer">Заказчк:</strong>

                            </p>

                            <p>
                                <strong id="_address">Адрес:</strong>

                            </p>

                            <p>
                                <strong id="_sum">Бюджет:</strong>

                            </p>
                            <p>
                                <strong id="_date">Дата начала работ:</strong>

                            </p>
                            <p>
                                <strong id="_days">Кол-во смен:</strong>

                            </p>
                            <div class="button">
                                <a href="#" id="_link" data-id="0" class="btn-custom">Перейти в заказ</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>

            </div>
        </div>
    </div>
</div>
<div class="modal modal-fade" id="event-modal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span
                            aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title">
                    Укажите тип доступности.
                </h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="event-index" value="">
                <form class="form-horizontal" id="eventForm">
                    @csrf
                    <input type="hidden" name="id" value="{{$machine->id}}">
                    <div class="form-group">
                        <label for="min-date" class="col-sm-4 control-label">Даты</label>
                        <div class="col-sm-7">
                            <div class="input-group">
                                <span>С</span>
                                <div class="form-item">
                                    <input name="event-start-date" id="event-start-date"
                                           data-toogle="datepicker_start" type="text" class="form-control"
                                           value="2012-04-05">
                                </div>
                                <span>По</span>
                                <div class="form-item">
                                    <input name="event-end-date" id="event-end-date" data-toogle="datepicker_end"
                                           type="text" class="form-control"
                                           value="2012-04-19">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="col-12">


                    <button type="button" style="width: 100%;" data-dismiss="modal" class="btn btn-danger"
                            id="busy">
                        Техника занята
                    </button>
                    <br>
                    <button type="button" style="width: 100%;" class="btn btn-primary" id="save-event">
                        Техника свободна
                    </button>
                    <br>
                    <button type="button" style="width: 100%;" class="btn btn-default" data-dismiss="modal">
                        Отмена
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>
@push('footer-scripts')
    <link rel="stylesheet" href="/css/calendar.css">
    <link href="/css/jquery.datetimepicker.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker.css"
          rel="stylesheet">
    <script src="/js/jquery.datetimepicker.full.min.js"></script>
    <script src="/js/calendar/datepicker.js"></script>
    <script src="/js/calendar/calendar.js?{{time()}}"></script>
    <script src="/js/date_format.js"></script>

    <script>
        var __container = [];

        function showMessage(e) {
            swal(e)
        }
        function showErrors(response) {
            var errors = '';
            var data = response.responseJSON;
            for (key in data) {
                errors += data[key] + '<br>';
            }
            swal(errors);
        }

        $(document).ready(function () {

            function refreshCard() {
                $.getJSON('/machine/{{$machine->id}}', {format: 'json'})
                    .done(function (response) {
                        // $('#machine_show').html('').html(response.data);
                        var dates = {
                            minDate: response.minDate,
                            maxDate: response.maxDate,
                            period: parsePeriod(response.period),
                        }
                        var reserve = response.reserve;
                        $('body').find('#calendar').remove()
                        $('<div id="calendar"></div>').appendTo('#_panel')
                        var calendarDates = $('body').find('#calendar').calendar({
                            customDayRenderer: function (element, date) {

                                for (key in reserve) {
                                     u = reserve[key]
                                    for(_k in u){
                                        var d = new Date(u[_k])
                                        if (date.getTime() == d.getTime()) {
                                            $(element).addClass('border');
                                        }
                                    }

                                }
                            },
                            enableRangeSelection: true,
                            header: 'hideHeader',
                            minDate: new Date(Date.parse(dates.minDate)),
                            maxDate: new Date(Date.parse(dates.maxDate)),
                            selectRange: function (e) {

                                editEvent({startDate: e.startDate, endDate: e.endDate});
                            },
                            enableContextMenu: true,
                            contextMenuItems: [
                                {
                                    text: 'Просмотр',
                                    click: showEvent
                                },
                                {
                                    text: 'Удалить',
                                    click: deleteEvent
                                }
                            ],
                            dataSource: dates.period,
                            renderEnd: function (e) {
                                initDatePikcer();

                                var currentMonth = new Date(Date.parse(dates.minDate)).getMonth();
                                var lastMonth = new Date(Date.parse(dates.maxDate)).getMonth();
                                var current_year = new Date(Date.parse(dates.minDate)).getFullYear();
                                if (!__container.length) {
                                    __container.push($('#calendar .year-title').html())
                                }
                                $('#calendar .month-container').each(function (idx, el) {

                                    if (idx < currentMonth) {
                                        if (__container.length) {
                                            if (__container[0] === $('#calendar .year-title').html()) {
                                                $(this).remove();
                                            }

                                        }
                                    }
                                    /*if (idx > lastMonth) {
                                        $(this).css("display", "none");
                                    }*/
                                });

                                $('body .border').each(function (e) {
                                    var p = $(this).parent();
                                    p.css('box-shadow', 'orange 0px -4px 1px 0px inset');
                                })

                            }
                        });


                    }).fail(function (response) {
                    showMessage('Ошибка.')
                })
            }

            function initDatePikcer() {
                $.datetimepicker.setLocale('ru')
                jQuery('#event-start-date').datetimepicker({
                    format: 'Y/m/d',
                    dayOfWeekStart: 1,
                    onShow: function (ct) {
                        this.setOptions({
                            maxDate: jQuery('#date_timepicker_end').val() ? jQuery('#date_timepicker_end').val() : false
                        })
                    },
                    timepicker: false
                });
                jQuery('#event-end-date').datetimepicker({
                    format: 'Y/m/d',
                    dayOfWeekStart: 1,
                    onShow: function (ct) {
                        this.setOptions({
                            minDate: jQuery('#event-start-date').val() ? jQuery('#event-start-date').val() : false
                        })
                    },
                    timepicker: false
                });
            }

            function editEvent(event) {
                console.log(event);
                $('span.error').remove();
                $('.error').each(function () {
                    $(this).removeClass('error');
                })

                $('body').find('#event-modal input[name="event-start-date"]').val(event ? dateFormat(event.startDate, 'yyyy/mm/dd') : '');
                $('body').find('#event-modal input[name="event-end-date"]').val(event ? dateFormat(event.endDate, 'yyyy/mm/dd') : '');
                $('body').find('#event-modal').modal();
            }

            function showEvent(event) {
                console.log(event);
                $('#proposal-modal p p').remove()
                if (event.type === 'order') {
                    $('<p>' + event.proposal.user.id_with_email + '</p>').insertAfter('#_customer')
                    $('<p>' + event.proposal.address + '</p>').insertAfter('#_address')
                    $('<p>' + event.proposal.sum_format + '</p>').insertAfter('#_sum')
                    $('<p>' + event.proposal.date + '</p>').insertAfter('#_date')
                    $('<p>' + event.proposal.days + '</p>').insertAfter('#_days')
                    $('#_link').attr('href', '/order/' + event.proposal.id + '/edit')
                    //  $(event.proposal.user_id).insertAfter('#_link')
                    $('body').find('#proposal-modal').modal();
                } else {
                    editEvent(event)
                }
            }

            function saveEvent(busy) {

                if (busy === undefined) {
                    busy = false
                }
                var startDate = $('#event-modal input[name="event-start-date"]').val();
                var endDate = $('#event-modal input[name="event-end-date"]').val();
                var url = busy ? 'set-busy' : 'push-event';
                $.ajax({
                    url: '/' + url,
                    type: 'POST',
                    data: $('body').find('#eventForm').serialize(),
                    success: function (response) {
                        var event = {
                            id: $('#event-modal input[name="event-index"]').val(),
                            startDate: startDate,
                            endDate: endDate
                        }

                        var dataSource = $('#calendar').data('calendar').getDataSource();

                        if (event.id) {
                            for (var i in dataSource) {
                                if (dataSource[i].id == event.id) {
                                    dataSource[i].startDate = event.startDate;
                                    dataSource[i].endDate = event.endDate;
                                }
                            }
                        }
                        else {
                            var newId = 0;
                            for (var i in dataSource) {
                                if (dataSource[i].id > newId) {
                                    newId = dataSource[i].id;
                                }
                            }
                            newId++;
                            event.id = newId;
                            dataSource.push(event);
                        }

                        // $('#calendar').data('calendar').setDataSource(dataSource);
                        $('#event-modal').modal('hide');

                        refreshCard(response.id)
                    },
                    error: function (response) {
                        showErrors(response)
                    }
                })

            }

            function parsePeriod(period) {
                for (key in period) {
                    var s = period[key]['startDate'];
                    var e = period[key]['endDate'];
                    period[key]['startDate'] = new Date(s.replace(/-/g, "/"))
                    period[key]['endDate'] = new Date(e.replace(/-/g, "/"))

                    switch (period[key]['type']) {
                        case 'free':
                            period[key]['name'] = 'Свободные дни'
                            period[key]['color'] = 'blue';
                            break;
                        case 'order':
                            period[key]['name'] = 'Заказ'

                            switch (period[key]['proposal']['status']) {
                                case 0:
                                case 1:
                                case 2:
                                    period[key]['color'] = '#71da73';
                                    break;
                                case 3:
                                    period[key]['color'] = '#da2d85';
                                    break;
                                case 4:
                                    period[key]['color'] = '#a94442';
                                    break;
                            }

                            break;
                        case 'reserve':
                            period[key]['name'] = 'Резерв'
                            period[key]['color'] = 'orange';
                            break;
                        case 'not':
                            period[key]['name'] = 'Недоступна'
                            period[key]['color'] = '#ffafaf';
                            break;
                        default:
                    }
                    period[key]['id'] = period[key]['id']
                    console.log(period);
                    //  period[key]['name'] = (period[key]['type'] == 'free') ? 'Свободные дни' : 'Заказ'
                }

                return period;
            }

            function deleteEvent(event) {

                $.ajax({
                    url: '/delete-event',
                    type: 'POST',
                    data: {id: event.id, _token: '{{csrf_token()}}'},
                    success: function (response) {
                        var dataSource = $('#calendar').data('calendar').getDataSource();
                        for (var i in dataSource) {
                            if (dataSource[i].id == event.id) {
                                dataSource.splice(i, 1);
                                break;
                            }
                        }
                        // $('#calendar').data('calendar').setDataSource(dataSource);
                        refreshCard(response.id)
                    },
                    error: function (r) {
                        showErrors(r)
                    }
                })

            }

            $('body').on('click', '#save-event', function () {
                saveEvent();
            });
            $('body').on('click', '#busy', function () {
                saveEvent(true);
            });

            refreshCard();
        })
    </script>
@endpush