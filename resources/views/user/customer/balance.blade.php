@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>@lang('transbaza_finance.balance_title')</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                @if(Session::has('success_payment'))
                    <div class="alert alert-success alert-message">
                        <button type="button" data-dismiss="alert" aria-label="Close" class="close"><span
                                    aria-hidden="true">×</span>
                        </button>
                        <i class="fa fa-check fa-lg"></i> {{Session::get('success_payment')}}
                    </div>
                @endif

                @if(Session::has('fail_payment'))
                    <div class="alert alert-danger alert-message">
                        <button type="button" data-dismiss="alert" aria-label="Close" class="close"><span
                                    aria-hidden="true">×</span>
                        </button>
                        <i class="fa fa-warning fa-lg"></i> {{Session::get('fail_payment')}}
                    </div>
                @endif
                <div class="clearfix"></div>
                <div class="search-wrap user-profile-wrap box-shadow-wrap">
                    <h1 class="title">@lang('transbaza_finance.my_finance_title')</h1>
                    <h2 class="title">@lang('transbaza_finance.balance')
                        <div class="finance_"> {{Auth::user()->getCurrentBalance(true)}}</div>
                    </h2>
                    <div class="detail-search">
                        <div class="hr-line"></div>

                        <form id="in_transaction_form">
                            @csrf

                            <div class="col-md-offset-2 col-md-8">
                                <div class="form-item">
                                    <label for="type-account" class="required">
                                        @lang('transbaza_finance.action_type')
                                        <div class="custom-select-exp">
                                            <select name="type" id="typeSelect">
                                                <option value="default"
                                                        selected>@lang('transbaza_finance.choose_type')</option>
                                                @customer
                                                <option value="in">@lang('transbaza_finance.refill')</option>
                                                @endCustomer
                                                <option value="out">@lang('transbaza_finance.withdrawal')</option>
                                            </select>
                                        </div>
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label class="required">@lang('transbaza_finance.type_sum')</label>
                                    <input name="sum" value=""
                                           type="text">
                                </div>
                            </div>
                            <div class="col-md-offset-3 col-md-6">
                                <div class="button" id="simple" @customer style="display: none" @endCustomer>
                                    <button data-type="submit" class="btn-custom submitButton"
                                            type="button">@lang('transbaza_finance.accept')</button>
                                </div>
                                @customer
                                <div class="button two-btn" id="hard" style="display: none">
                                    <button data-type="account_payment" type="button"
                                            class="btn-custom submitButton">@lang('transbaza_finance.payment_by_account')</button>
                                    <span></span>
                                    <button data-type="card_payment" type="button"
                                            class="btn-custom submitButton">@lang('transbaza_finance.payment_by_card')</button>
                                </div>
                                @endCustomer
                            </div>

                        </form>

                    </div>
                    <div class="clearfix"></div>
                    <div class="detail-search">

                        @if($balances_wait)
                            <h3>@lang('transbaza_finance.filter')</h3>
                            <div class="hr-line"></div>
                            <form id="transactions_filters">
                                <div class="filter-list-wrap col-list two-cols">
                                    <div class="col col-long">
                                        <div class="form-item">
                                            Тип
                                            <div class="custom-select-exp">
                                                <select name="status" id="">
                                                    <option value="">@lang('transbaza_finance.choose_type')</option>
                                                    @foreach(\App\Finance\FinanceTransaction::STATUS_LNG as $key => $item)
                                                        <option value="{{$key}}">{{$item}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col col-medium">
                                        <div class="form-item">
                                            <label for="">
                                                @lang('transbaza_finance.filter_sum_from')
                                                <input type="number" name="sum_from">
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col col-medium">
                                        <div class="form-item">
                                            <label for="">
                                                @lang('transbaza_finance.filter_sum_to')
                                                <input name="sum_to" type="number">
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col col-long">
                                        <div class="form-item image-item end">
                                            <label for="date-picker-balance-trans">
                                                @lang('transbaza_finance.filter_date_from')
                                                <input type="text" id="date-picker-balance-trans" name="date_from"
                                                       data-toggle="datepicker"
                                                       placeholder="2018/08/08" autocomplete="off">
                                                <span class="image date"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col col-long">
                                        <div class="form-item image-item end">
                                            <label for="date-picker-balance-trans-end">
                                                @lang('transbaza_finance.filter_date_to')
                                                <input type="text" id="date-picker-balance-trans-end" name="date_to"
                                                       data-toggle="datepicker"
                                                       placeholder="2018/08/08" autocomplete="off">
                                                <span class="image date"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="btn-col">
                                        <div class="button">
                                            <button type="button" id="transactions_reset" class="btn-custom">
                                                @lang('transbaza_finance.filter_reset')
                                            </button>
                                        </div>

                                    </div>
                                    <div class="btn-col">
                                        <div class="button">
                                            <button class="btn-custom">Поиск</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <h2>@lang('transbaza_finance.my_transactions')</h2>
                            <div class="hr-line"></div>
                            <div class="table-responsive adaptive-table">
                                <table class="table table-striped table-bordered"
                                       style="width:100%" id="transactions_processed_table">
                                    <thead>
                                    <tr>
                                        <th>@lang('transbaza_finance.date')</th>
                                        <th>@lang('transbaza_finance.sum')</th>
                                        <th>@lang('transbaza_finance.status')</th>
                                        <th>@lang('transbaza_finance.transaction_type')</th>
                                        <th>@lang('transbaza_finance.initiator')</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>

                            <div class="list-proposals">
                                <h1>@lang('transbaza_finance.transactions')</h1>
                                <p id="count_1"></p>
                                <div class="proposal-items div_1">
                                    {{--@foreach($entities as $entity)--}}

                                    <div class="item">
                                        {{--<div class="button">--}}
                                        {{--<a  href="#" class="btn-custom">Просмотр</a>--}}
                                        {{--<a href="#" class="btn-custom edit-entity">Редактировать</a>--}}
                                        {{--<a href="#" class="btn-custom">Удалить</a>--}}
                                        {{--</div>--}}
                                    </div>
                                    {{--@endforeach--}}
                                </div>
                            </div>
                        @else
                            <div class="not-found-wrap">
                                <h3>@lang('transbaza_finance.empty_transactions')</h3>
                            </div>
                        @endif
                    </div>
                    <div class="detail-search">
                        @if($balances)
                            <h3>@lang('transbaza_finance.filter')</h3>
                            <div class="hr-line"></div>
                            <form id="balance_filters">
                                <div class="filter-list-wrap col-list two-cols">

                                    {{--  <div class="col col-long">
                                          <div class="form-item">
                                              Тип
                                              <div class="custom-select-exp">
                                                  <select name="type" id="">
                                                      <option value="">Выберите тип</option>
                                                      @foreach(\App\User\BalanceHistory::TYPES_LNG as $key => $item)
                                                          <option value="{{$key}}">{{$item}}</option>
                                                      @endforeach
                                                  </select>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="col col-medium">
                                          <div class="form-item">
                                              <label for="">
                                                  Сумма от
                                                  <input type="number" name="sum_from">
                                              </label>
                                          </div>
                                      </div>
                                      <div class="col col-medium">
                                          <div class="form-item">
                                              <label for="">
                                                  Сумма до
                                                  <input name="sum_to" type="number">
                                              </label>
                                          </div>
                                      </div>--}}
                                    <div class="col col-long">
                                        <div class="form-item image-item end">
                                            <label for="date-picker-balance">
                                                @lang('transbaza_finance.filter_tr_date_from')
                                                <input type="text" name="date_from" id="date-picker-balance"
                                                       data-toggle="datepicker"
                                                       placeholder="2018/08/08" autocomplete="off">
                                                <span class="image date"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col col-long">
                                        <div class="form-item image-item end">
                                            <label for="date-picker-balance-end">
                                                @lang('transbaza_finance.filter_tr_date_to')
                                                <input type="text" name="date_to" id="date-picker-balance-end"
                                                       data-toggle="datepicker"
                                                       placeholder="2018/08/08" autocomplete="off">
                                                <span class="image date"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="two-btns">
                                        <div class="btn-col">
                                            <div class="button">
                                                <button type="button" id="balance_history_reset" class="btn-custom">
                                                    @lang('transbaza_finance.filter_tr_reset')
                                                </button>
                                            </div>

                                        </div>
                                        <div class="btn-col">
                                            <div class="button">
                                                <button type="submit"
                                                        class="btn-custom">@lang('transbaza_finance.filter_tr_show')</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <h2>@lang('transbaza_finance.statement')</h2>
                            <div class="hr-line"></div>
                            <b id="period_start"></b>
                            <div class="table-responsive adaptive-table">

                                <table class="table table-striped table-bordered"
                                       style="width:100%" id="balance_history_table">
                                    <thead>
                                    <tr>
                                        <th style="width: 8.5rem">@lang('transbaza_finance.table_date')</th>
                                        <th style="width: 13rem;">@lang('transbaza_finance.table_refill')</th>
                                        <th style="width: 13rem;">@lang('transbaza_finance.table_withdrawal')</th>
                                        <th>@lang('transbaza_finance.table_comment')</th>
                                        <th style="width: 13rem;">@lang('transbaza_finance.table_balance')</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>

                            </div>
                            <div class="list-proposals">
                                <h1>@lang('transbaza_finance.statement')</h1>
                                <p id="count_2"></p>
                                <div class="proposal-items div_2">
                                    {{--@foreach($entities as $entity)--}}
                                    <div class="item">
                                        {{--<div class="button">--}}
                                        {{--<a  href="#" class="btn-custom">Просмотр</a>--}}
                                        {{--<a href="#" class="btn-custom edit-entity">Редактировать</a>--}}
                                        {{--<a href="#" class="btn-custom">Удалить</a>--}}
                                        {{--</div>--}}
                                    </div>
                                    {{--@endforeach--}}
                                </div>
                            </div>
                            <b id="period_end"></b>
                        @else
                            <div class="not-found-wrap">
                                <h3>@lang('transbaza_finance.statement_empty')</h3>
                            </div>
                        @endif
                    </div>


                </div>

            </div>
        </div>
    </div>

    <!-- Modal -->

@endsection

@push('after-scripts')
    <script src="/js/tables/dataTables.js"></script>
    <script src="/js/tables/tableBs.js"></script>
    <script>

        $(document).ready(function () {
            $('#tabs-panel a').click(function () {
                $('#tabs-panel a').removeClass('black')
                $(this).addClass('black')
            })

            var balance_history_table = $('#balance_history_table').DataTable({
                // "ajax": "/balance_history_table",
                data: [],
                "autoWidth": false,
                "ordering": false,
                "searching": false,
                "paging": false,
                "columns": [
                    {
                        "data": "created_at",
                    },
                    {
                        "data": "refill",
                        className: 'align-right'
                    },
                    {
                        "data": "withdrawal",
                        className: 'align-right'
                    },
                    {"data": "reason",},
                    {
                        "data": "new_sum_format",
                        className: 'align-right'
                    },

                ],

            })

            var transactions_history_table = $('#transactions_processed_table').DataTable({
                // "ajax": "/transactions_history_table",
                data: [],
                "autoWidth": false,
                "ordering": false,
                "searching": false,
                "paging": false,
                "columns": [
                    {"data": "created_at",},
                    {"data": "sum_format",},
                    {"data": "status",},
                    {"data": "type",},
                    {"data": "balance_type",},
                    /*   {"data": "_admin",},*/
                ],

            })

            function getBalanceHistory(filters = '') {


                var value = $.ajax({
                    url: '/balance_history_table?' + filters,
                    async: false
                }).responseText;
                value = JSON.parse(value);

                $('#period_start').html('@lang('transbaza_finance.balance_begining_period') ' + value['period_start'] + ' руб.')
                $('#count_2').html('@lang('transbaza_finance.show_entries')  ' + value['count'] + ' @lang('transbaza_finance.show_entries_pref')  ' + value['count'] + ' @lang('transbaza_finance.show_entries_count') .')
                $('#period_end').html('@lang('transbaza_finance.balance_end_period') ' + value['period_end'] + ' руб.')
                balance_history_table.clear().draw();
                balance_history_table.rows.add(value['data'])
                    .draw();


                setMobileCard({
                    created_at: '@lang('transbaza_finance.table_date')',
                    refill: '@lang('transbaza_finance.table_refill')',
                    withdrawal: '@lang('transbaza_finance.table_withdrawal')',
                    reason: '@lang('transbaza_finance.table_comment')',
                    new_sum_format: '@lang('transbaza_finance.table_balance')',
                }, value['data'], '/balance_history_table', '', false, 'div_2')

            }

            $('#typeSelect').on('change', function () {
                var type = $(this).val();
                if (type === 'in') {
                    $('#hard').show()
                    $('#simple').hide()
                } else {
                    $('#hard').hide()
                    $('#simple').show()
                }
            })

            function getTransactions(filters = '') {


                var value = $.ajax({
                    url: '/transactions_history_table?' + filters,
                    async: false
                }).responseText;
                value = JSON.parse(value);
                $('#count_1').html('@lang('transbaza_finance.show_entries')  ' + value['count'] + ' @lang('transbaza_finance.show_entries_pref')  ' + value['count'] + ' @lang('transbaza_finance.show_entries_count') .')
                transactions_history_table.clear().draw();
                transactions_history_table.rows.add(value['data'])
                    .draw();

                setMobileCard({
                    created_at: '{{trans('transbaza_finance.date')}}',
                    sum_format: '{{trans('transbaza_finance.sum')}}',
                    type: '{{trans('transbaza_finance.transaction_type')}}',
                    status: '{{trans('transbaza_finance.status')}}',

                }, value['data'], '/transactions_history_table', '', false, 'div_1')

            }

            $(document).on('submit', '#balance_filters', function (e) {
                e.preventDefault();
                var params = $('#balance_filters').serialize();
                getBalanceHistory(params)
                //  balance_history_table.ajax.url('/balance_history_table?' + params).load();
            });
            $(document).on('click', '#balance_history_reset', function (e) {
                e.preventDefault();
                //  balance_history_table.ajax.url('/balance_history_table').load();
                getBalanceHistory()
            })


            $(document).on('submit', '#transactions_filters', function (e) {
                e.preventDefault();
                var params = $('#transactions_filters').serialize();
                //  transactions_history_table.ajax.url('/transactions_history_table?' + params).load();
                getTransactions(params)
            });
            $(document).on('click', '#transactions_reset', function (e) {
                e.preventDefault();
                //    transactions_history_table.ajax.url('/transactions_history_table').load();
                getTransactions()
            })

            getTransactions()
            getBalanceHistory()

            $('.submitButton').on('click', function (e) {
                e.preventDefault();
                var type = $(this).data('type')
                var submit = $('.submitButton');
                submit.hide();
                $.ajax({
                    url: '/finance',
                    type: 'POST',
                    data: $('#in_transaction_form').serialize() + '&transaction_type=' + type,
                    success: function (data) {
                        refreshBalance();
                        showMessage(data.message);
                        if (data.hasOwnProperty('formUrl')) {
                            setTimeout(function () {
                                location.href = data.formUrl;
                            }, 2000)
                        } else {
                            submit.show();
                        }
                    },
                    error: function (message) {
                        showErrors(message);
                        submit.show();
                    }
                })
            })
        })
    </script>
@endpush