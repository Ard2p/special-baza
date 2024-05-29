<table style="width: 100%; border: 2px #d1d1d1 solid; font-size: 14px">

    <tr>
        <td style="font-size: 16px">Минимальный заказ</td>
        <td style="font-size: 16px">{{$vehicle->min_order}} {{$vehicle->min_order_type === 'shift' ? 'смен.' : 'ч.'}}</td>
    </tr>
    <tr>
        <td style="font-size: 16px">Стоимость минимального заказа</td>
        <td style="font-size: 16px">{{numfmt_format_currency($fmt, ($vehicle->min_order_type === 'shift' ? $vehicle->sum_day : $vehicle->sum_hour)  / 100, $vehicle->currency)}} {{$vehicle->min_order_type === 'shift' ? 'смен.' : 'ч.'}}</td>
    </tr>
    @if($vehicle->is_contractual_delivery)
        <tr>
            <td style="font-size: 16px">Стоимость доставки</td>
            <td style="font-size: 16px">{{numfmt_format_currency($fmt, $vehicle->contractual_delivery_cost / 100, $vehicle->currency)}}</td>
        </tr>
    @endif
</table>