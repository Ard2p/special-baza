@extends('invoice.layout')
@section('content')
    <table width="100%">
        <tr>
            <td style="width: 155mm;">
                <div style="width:155mm; text-align: left">
                   {{-- <img style="height: 70px" src="https://trans-baza.ru/img/logo-ru.svg">--}}

                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div style="text-align:center;  font-weight:bold;">
                    Платежное поручение
                </div>
            </td>
        </tr>
    </table>


    <table width="100%" cellpadding="2" cellspacing="2" class="invoice_bank_rekv">
        <tr>
            <td colspan="2" rowspan="2" style="min-height:13mm; width: 105mm;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="height: 13mm;">
                    <tr>
                        <td valign="top">
                            <div>{{$dispatcher->bank}}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="bottom" style="height: 3mm;">
                            <div style="font-size:10pt;">Банк получателя</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="min-height:7mm;height:auto; width: 25mm;">
                <div>БИK</div>
            </td>
            <td rowspan="2" style="vertical-align: top; width: 30mm;">
                <div style=" height: 7mm; line-height: 7mm; vertical-align: middle;">{{$dispatcher->bik}}</div>
                <div>{{$dispatcher->ks}}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25mm;">
                <div>Сч. №</div>
            </td>
        </tr>
        <tr>
            <td style="min-height:6mm; height:auto; width: 50mm;">
                <div>ИНН {{$dispatcher->inn}}</div>
            </td>
            <td style="min-height:6mm; height:auto; width: 55mm;">
                <div>КПП {{$dispatcher->kpp}}</div>
            </td>
            <td rowspan="2" style="min-height:19mm; height:auto; vertical-align: top; width: 25mm;">
                <div>Сч. №</div>
            </td>
            <td rowspan="2" style="min-height:19mm; height:auto; vertical-align: top; width: 30mm;">
                <div>{{$dispatcher->rs}}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="min-height:13mm; height:auto;">

                <table border="0" cellpadding="0" cellspacing="0" style="height: 13mm; width: 105mm;">
                    <tr>
                        <td valign="top">
                            <div>{{$dispatcher->name}}</div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="bottom" style="height: 3mm;">
                            <div style="font-size: 10pt;">Получатель</div>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
    <br/>

    <div style="font-weight: bold; font-size: 16pt; padding-left:5px;">
        Счет № {{$invoice->number}} от {{$invoice->created_at->format('d.m.Y')}}
    </div>
    <br/>

    <div style="background-color:#000000; width:100%; font-size:1px; height:2px;">&nbsp;</div>

    <table width="100%">
        <tr>
            <td style="width: 30mm;">
                <div style=" padding-left:2px;">Поставщик:</div>
            </td>
            <td>
                <div style="font-weight:bold;  padding-left:2px; font-size: 12px">
                    {{$dispatcher->name}}, ИНН: {{$dispatcher->inn}}, КПП: {{$dispatcher->kpp}}
                    , {{$dispatcher->register_address}}
                </div>
            </td>
        </tr>
        <tr>
            <td style="width: 30mm;">
                <div style=" padding-left:2px;">Покупатель:</div>
            </td>
            <td>
                <div style="font-weight:bold;  padding-left:2px; font-size: 12px">
                    {{$customer instanceof \App\User\EntityRequisite ? $customer->name : $customer->full_name}}, ИНН: {{$customer->inn}}, КПП: {{$customer->kpp}}, {{$customer->register_address}}
                </div>
            </td>
        </tr>
    </table>


    <table class="invoice_items" width="100%" cellpadding="2" cellspacing="2">
        <thead>
        <tr>
            <th style="width:13mm;">№</th>

            <th>Товары (работы, услуги)</th>
            <th style="width:20mm;">Кол-во</th>
            <th style="width:17mm;">Ед.</th>
            <th style="width:27mm;">Цена</th>
            <th style="width:27mm;">Сумма</th>
        </tr>
        </thead>
        <tbody>

            <tr>
                <td align="center">1</td>
                <td align="left">Оказание услуг по предоставлению автотранспорта, строительной техники и механизмов </td>
                <td align="right">1</td>
                <td align="left">шт</td>
                <td align="right">{{humanSumFormat($invoice->sum)}}</td>
                <td align="right">{{humanSumFormat($invoice->sum)}}</td>
            </tr>

        </tbody>
    </table>

    <table border="0" width="100%" cellpadding="1" cellspacing="1">
        <tr>
            <td></td>
            <td style="width:27mm; font-weight:bold;  text-align:right; white-space: nowrap">Итого:</td>
            <td style="width:27mm; font-weight:bold;  text-align:right;">{{humanSumFormat($invoice->sum, 2)}}</td>
        </tr>
        <tr>
            <td></td>
            <td style="width:27mm; font-weight:bold;  text-align:right; white-space: nowrap">В т.ч. НДС:</td>
            <td style="width:27mm; font-weight:bold;  text-align:right;">{{humanSumFormat($invoice->vat_amount, 2)}}</td>
        </tr>
        <tr>
            <td></td>
            <td style="width:27mm; font-weight:bold;  text-align:right; white-space: nowrap">Всего к оплате:</td>
            <td style="width:27mm; font-weight:bold;  text-align:right;">{{humanSumFormat($invoice->sum, 2)}}</td>
        </tr>
    </table>

    <br/>
    <div>
        Всего наименований 1 на сумму {{humanSumFormat($invoice->sum)}} рублей.<br/>
        {{(new NumberFormatter("ru", NumberFormatter::SPELLOUT))->format(round($invoice->sum / 100))}} рублей 00 копеек
    </div>
    <br/><br/>
    <div style="background-color:#000000; width:100%; font-size:1px; height:2px;">&nbsp;</div>
    <br/>

    <div>Руководитель ______________________ </div>
    <br/>

    <div>Главный бухгалтер ______________________ </div>
    <br/>

    <div style="width: 85mm;text-align:center;">М.П.</div>
    <br/>


    <div style="width:800px;text-align:left;font-size:10pt;">Счет действителен к оплате в течении трех дней.</div>

@endsection
