<p style="min-height:13mm;text-align: right">
    Приложение к оферте для заказчиков <br>
    http://www.trans-baza.ru/policy
</p>


<h3 style="text-align: center">ЗАЯВКА НА АРЕНДУ ТЕХНИКИ С ЭКИПАЖЕМ №{{$order->id}}</h3>

<table width="100%" cellpadding="2" cellspacing="2" class="invoice_bank_rekv">
    <tr>
        <td>
            <ol>
                <li>
                    Дата подачи заявки {{$order->created_at->format('d.m.Y')}}
                </li>
                @isset($invoice)
                <li>
                    Заказчик: {{$invoice->requisite->name}}
                </li>
                @endisset
                <li>
                    Тип и количество требуемой техники:

                </li>
            </ol>
            <table width="100%" cellpadding="2" cellspacing="2" class="invoice_bank_rekv">
                <tr>
                    <td>
                        № п.п.
                    </td>
                    <td>
                        Тип техники
                    </td>
                    <td>
                        Количество
                    </td>
                    <td>
                        Часы/смена
                    </td>
                    <td>
                        Кол-во часов/смен
                    </td>
                    <td>
                        Стоимость
                    </td>
                </tr>
                @foreach($vehicles as $i => $vehicle)
                    <tr style="text-align: center">
                        <td>
                           {{++$i}}
                        </td>
                        <td>
                            {{$vehicle->_type->name}}
                        </td>
                        <td>
                            1
                        </td>

                        <td>
                            {{$vehicle->pivot->order_type === 'shift' ? 'Смена'  : 'Часы'}}
                        </td>
                        <td>
                            {{$vehicle->pivot->order_duration}}
                        </td>

                        <td>
                            {{humanSumFormat($vehicle->pivot->amount)}} руб.
                        </td>
                    </tr>
                @endforeach
            </table>
            <p>Дополнительные услуги:</p>
            <ul>
                @foreach($vehicles as $vehicle)

                    <li>Доставка: {{humanSumFormat($vehicle->pivot->delivery_cost)}} руб.</li>
                @endforeach
            </ul>
            <p>Итого на сумму: {{humanSumFormat($order->amount)}} руб.</p>
            <ol start="3">
                <li>
                    Дата подачи техники: {{$order->date_from->format('d.m.Y')}} <br>
                    время подачи {{$order->start_time}}
                </li>
                <li>
                    Объект, адрес выполнения работ: {{$order->address}}
                </li>
                <li>
                    Наименование Исполнителя:
                </li>
            </ol>
        </td>
    </tr>
</table>

<p>
    Продолжительность 1-ой смены составляет 8 мото/часов и включают в себя 1 час подачи и 7 часов работы на территории
    Заказчика.
    При работе больше 7 часов каждый полный или неполный час оплачивается дополнительно в двойном размере от
    установленной стоимости 1 м/ч.
</p>
<p> При загородной работе (от 15 км, либо затрудненное транспортное сообщение до объекта):
</p>
<ul>
    <li> Питанием обеспечивает заказчик</li>
    <li>Проживанием и санитарно-бытовыми условиями обеспечивает Заказчик</li>
</ul>