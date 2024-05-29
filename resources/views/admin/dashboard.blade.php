<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="fa fa-rub"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Системный кошелек</span>
                <span class="info-box-number">{{\App\Option::find('system_cash')->value / 100}}
                    <small></small></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-orange"><i class="fa fa-rub"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Баланс SMS-API</span>
                <span class="info-box-number">{{(new \App\Service\Sms())->get_balance()}}
                    <small></small></span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>

    <div class="col-md-12">
        <h4>История баланса системного кошелька</h4>
        <table class="table table-striped" id="cash_history" width="100%">
            <thead>
            <tr>
                <th class="row-header">
                    Сумма изменения

                </th>
                <th class="row-header">
                    Старый баланс

                </th>
                <th class="row-header">
                    Новый баланс
                </th>
                <th class="row-header">
                    Причина
                </th>
            </tr>
            </thead>
            <tbody>
            @foreach(\App\SystemCashHistory::orderBy('id', 'DESC')->get() as $item)
                <tr>
                    <td>
                        {{$item->sum / 100}}
                    </td>
                    <td>
                        {{$item->old_sum / 100}}
                    </td>
                    <td>
                        {{$item->new_sum / 100}}
                    </td>
                    <td>
                        {{$item->reason}}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tr></tr>
            </tfoot>
        </table>
    </div>
</div>


