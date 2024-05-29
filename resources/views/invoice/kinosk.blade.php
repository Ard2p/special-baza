<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>rental-contract</title>
    <style>
        body {
            border: 2px solid #000;
            padding: 25px;

            height: 1115px;
            margin: 0 auto;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td {
            border: 1px solid #000;
            height: 20px;
        }

        .logo {
            position: relative;
            height: 200px;
        }

        .logo-img {
            box-sizing: border-box;
            width: 130px;
            height: 90px;
            font-weight: 700;
            text-align: center;
            padding: 15px;
            font-size: 22px;
        }

        .logo-contact h3 {
            font-size: 22px;
            font-weight: 700;
            position: absolute;
            top: 0;
            left: 180px;
        }

        .logo-contact span {
            display: block;
            font-size: 12px;
            position: absolute;
            top: 45px;
            left: 181px;
        }

        .one {
            text-transform: uppercase;
            font-size: 10px;


        }

        .two,
        .three {
            font-size: 10px;
            line-height: 10px;
        }

        .two tr:nth-child(2) {
            font-size: 10px;
        }

        .three tr:first-child,
        .two tr:first-child,
        .two tr:nth-child(2),
        .black {
            text-align: center;
            color: #fff;
            background: #000;

        }

        .box::before {
            content: "";
            display: inline-block;
            height: 10px;
            width: 10px;
            border: 1px solid #000;
        }

        .border-bottom {
            width: 400px;
            height: 1px;
            border-bottom: 1px solid #000;
        }

    </style>
</head>
<body>
<table class="table" style="font-size: 10px;">
    <tbody>
    <tr>
        <td class="logo" width="40" style="height: 130px; border: transparent;">
            <div class="logo-img">
                <img src="https://kinosk.com/img/logo-en.svg">
            </div>

        </td>
        <td style="border: transparent; text-align: left; padding-left: 10px;">

            <h4>
                KINOSK PTE. LTD.
            </h4>

            <span style="font-size: 8px">
                      2 SIMS DRIVE
                    <br>
                        #14-02 SIMS URBAN OASIS
                      <br>
                        SINGAPORE 387386
                      <br>
                      <br>
                        Beneficiary's name: KINOSK PTE. LTD.
                      <br>
                        Beneficiary's Acc: 0179061342
                      <br>
                        SWIFT: DBSSSGSG
                      <br>
                        Beneficiary's Bank: DBS Bank LTD.
                      <br>
                      <br>
                        Bank Address: 12 Marina Boulevard, DBS Asia Central, Marina Bay Financial Centre Tower 3, Singapore 018982 Country: Singapore
                    </span>

        </td>
        <td style="border: transparent;text-align: center">
            <h1 style="top: 0">RENTAL</h1>
            <h3 style=" margin: 0">Invoice #{{$invoice->number}}</h3>
        </td>

    </tr>
    </tbody>
</table>
<p></p>
<table style="height: 203px; width: 100%; border-collapse: collapse; font-size: 12px" border="1">
    <tbody>
    <tr style="height: 38px;">
        <td style="width: 6.51864%; height: 38px; border: none; text-align: center; font-size: 12px"><strong>TO</strong>
        </td>
        <td style="width: 58.8796%; height: 38px; border-left: none; border-top: none; border-right: none; border-bottom-style: solid; border-bottom-color: black;"
            colspan="3"> 
            <strong> Account name: {{$invoice->requisite->account_name}}</strong>


        </td>
        <td style="width: 2.60747%; height: 38px; border: none;"> </td>
        <td style="width: 15.8562%; height: 38px; border-left: none; border-bottom: 1px solid black; border-right: 1px solid black;">
            @php
                $date_to = (clone $order->date_from);
                $vehicle =  $order->vehicles->first();
                $order_type = $vehicle->pivot->order_type;
                $order_duration = $vehicle->pivot->order_duration;
              if($order_type === 'shift') {
                  $date_to->addDays($order_duration - 1)->endOfDay();
              }else {
                  $date_to->addHours($order_duration);
              }
            @endphp
            DATE AND TIME OUT {{$date_to}}
        </td>
        <td style="width: 16.1381%; height: 38px; border-left: none; border-bottom: 1px solid black; border-right: none;">
            DATE AND TIME IN {{$order->date_from}}
        </td>
    </tr>
    <tr style="height: 39px;">
        <td style="width: 6.51864%; height: 39px; border: none;"> 

        </td>
        <td style="width: 58.8796%; height: 39px; border-left: none; border-top: none; border-right: none; border-bottom-style: solid; border-bottom-color: black;"
            colspan="3"> 
            <strong> Account: </strong> {{$invoice->requisite->account}}
        </td>
        <td style="width: 2.60747%; height: 39px; border: none;"> </td>
        <td style="width: 15.8562%; height: 39px; border-left: none; border-bottom: 1px solid black; border-right: 1px solid black;">
            PICKED UP BY
        </td>
        <td style="width: 16.1381%; height: 39px; border-left: none; border-bottom: 1px solid black; border-top: 1px solid black; border-right: none">
            RECEIVED BY
        </td>
    </tr>
    <tr style="height: 36px;">
        <td style="width: 6.51864%; height: 36px; border: none;"> </td>
        <td style="width: 58.8796%; height: 36px; border-left: none; border-top: none; border-right: none; border-bottom-style: solid; border-bottom-color: black;"
            colspan="3">&nbsp;
            <strong> Swift:</strong> {{$invoice->requisite->swift}}
        </td>
        <td style="width: 2.60747%; height: 36px; border: none;"> </td>
        <td style="width: 15.8562%; height: 36px; border-left: none; border-bottom: 1px solid black; border-right: 1px solid black;">
            DRIVER'S LICENSE NO.
        </td>
        <td style="width: 16.1381%; height: 36px; border-bottom: 1px solid black; border-right: none">LIC. PLATE
            NO.
        </td>
    </tr>
    <tr style="height: 36px;">
        <td style="width: 6.51864%; height: 36px; border: none;"> </td>
        <td style="width: 58.8796%; height: 36px; border-left: none; border-top: none; border-right: none; border-bottom-style: solid; border-bottom-color: black;"
            colspan="3"> 
            <strong>Beneficiary`s bank: </strong>{{$invoice->requisite->beneficiary_bank}}.
            <strong>Code:</strong> {{$invoice->requisite->code}}.
            <strong>Bank Address:</strong> {{$invoice->requisite->bank_address}}
        </td>
        <td style="width: 2.60747%; height: 36px; border: none;"> </td>
        <td style="height: 36px; width: 31.9943%; border-left: none; border-bottom: 1px solid black; border-right: none"
            colspan="2">
            CREDIT CARD NO.
        </td>
    </tr>
    <tr style="height: 38px;">
        <td style="width: 6.51864%; height: 38px; border: none;"><strong>PHONE NO.</strong></td>
        <td style="width: 29.4398%; height: 38px; border-left: none; border-top: none; border-right: none; border-bottom-style: solid; border-bottom-color: black;">
             +{{$order->user->phone}}
        </td>
        <td style="width: 3.93766%; height: 38px; border: none;">
            <p style="text-align: center;"><strong>P.O NO</strong></p>

        </td>
        <td style="width: 25.5021%; height: 38px; border-left: none; border-top: none; border-right: none; border-bottom-style: solid; border-bottom-color: black;">
             
        </td>
        <td style="width: 2.60747%; height: 38px; border: none;"> </td>
        <td style="height: 38px; width: 31.9943%; border: none;" colspan="2">EXP.DATE</td>
    </tr>
    <tr style="height: 16px;">
        <td style="width: 6.51864%; height: 16px; border: none;"> </td>
        <td style="width: 29.4398%; height: 16px; border: none;"> </td>
        <td style="width: 3.93766%; height: 16px; border: none;"> </td>
        <td style="width: 25.5021%; height: 16px; border: none;"> </td>
        <td style="width: 2.60747%; border: none; height: 16px;"> </td>
        <td style="height: 16px; width: 33.9943%; border: none;" colspan="2">
            <table style="border-collapse: collapse; width: 100%;" border="0">
                <tbody>
                <tr height="18px">
                    <td style="border: transparent"></td>
                    <td colspan="3" style="border: transparent">
                        <span class="box" style="font-size: 7px; margin-right: 3px">
                            <span style="display: inline-block;  vertical-align: middle">cash</span>
                    </span>

                        <span class="box" style="font-size: 7px; margin-right: 3px">
                        <span style="display: inline-block;vertical-align: middle">cheque</span>
                    </span>

                        <span class="box" style="font-size: 7px; margin-right: 3px">
                        <span style="display: inline-block;  vertical-align: middle">debit card</span>
                    </span>

                        <span class="box" style="font-size: 7px; margin-right: 3px">
                            <span style="display: inline-block; vertical-align: middle">credit card</span>
                    </span>

                        <span class="box" style="font-size: 7px;">
                            <span style="display: inline-block; width: 15px; vertical-align: middle">other</span>
                    </span>
                    </td>
                </tr>
                </tbody>
            </table>
    </tr>
    </tbody>
</table>
<table class="two">
    <tr height="10px">
        <td width="30px" rowspan="2" style="border-right: 1px solid #fff">DESCRIPTION</td>
        <td width="260px" colspan="4" style="border-color: #fff">RENTAL RATE</td>
        <td width="55px" rowspan="2">AMOUNT</td>

    </tr>
    <tr height="10px">
        <td style="border-color: #fff">MINIMUM</td>
        <td style="border-color: #fff">DAY</td>
        <td style="border-color: #fff">WEEK</td>
        <td style="border-color: #fff">MONTH</td>

    </tr>
    @foreach($vehicles as $vehicle)
        @php
            $vehicle->_type->localization();

        @endphp
        <tr height="10px">
            <td width="30px">{{$vehicle->_type->name}} {{$vehicle->pivot->order_duration}} {{$vehicle->pivot->order_type === 'shift' ? trans('transbaza_register_order.min_shift') : trans('transbaza_register_order.min_hour')}}</td>
            <td width="55px">{{$vehicle->min_order}} {{$vehicle->min_order_type === 'shift' ? trans('transbaza_register_order.min_shift') : trans('transbaza_register_order.min_hour')}}</td>
            <td width="55px"> {{numfmt_format_currency($fmt, $vehicle->sum_day / 100, $vehicle->currency)}}</td>
            <td width="55px">-</td>
            <td width="55px">-</td>
            <td width="75px">{{numfmt_format_currency($fmt, $vehicle->pivot->amount/ 100, $vehicle->currency)}}</td>
        </tr>
    @endforeach
</table>
<table class="three">
    <tr height="10px">
        <td>SHORTAGE / BREAKAGE / SALE ITEMS</td>
        <td>QTY.OUT</td>
        <td>QTY.USED</td>
        <td>UNIT PRICE</td>
        <td>AMOUNT</td>
    </tr>
    <tr height="10px">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>

    <tr height="10px">
        <td colspan="3" style="border: transparent">EQUIPMENT USED LACATION:<span style="text-decoration: underline">{{$order->address}}</span>
        </td>
        <td style="text-align: center">RENTALS</td>
        <td></td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent"><span style="text-decoration: underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        </td>
        <td style="text-align: center">OTHER</td>
        <td></td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent">DELIVERED TO:
            <span class="box">
                    ABOVE
                </span>
            <span class="box">
                    OTHER
                </span>
            <span style="text-decoration: underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        </td>
        <td style="text-align: center">DELIVERY</td>
        <td>{{numfmt_format_currency($fmt, $order->vehicles->sum('pivot.delivery_cost') / 100, $invoice->payment->currency)}}</td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent"><span style="text-decoration: underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        </td>
        <td style="text-align: center">SUBTOTAL</td>
        <td></td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent">CONDITIONS</td>
        <td></td>
        <td></td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent"></td>
        <td></td>
        <td></td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent"></td>
        <td style="text-align: center">TAX</td>
        <td></td>
    </tr>

    <tr height="10px">
        <td colspan="3" style="border: transparent">SIGNATURE <span style="text-decoration: underline">X &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        </td>
        <td style="text-align: center">DEPOSIT</td>
        <td></td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent"></td>
        <td class="black" style="text-align: center">TOTAL</td>
        <td>{{numfmt_format_currency($fmt, ($invoice->sum + $invoice->tax) / 100, $invoice->payment->currency)}}</td>
    </tr>
    <tr height="10px">
        <td colspan="3" style="border: transparent; text-align: right; padding-right: 15px;">RENTAL IS CHARGED FOR TIME
            OUT, NOT TIME USED.
        </td>
        <td style="text-align: center">REFUND</td>
        <td></td>
    </tr>
</table>

</body>
</html>