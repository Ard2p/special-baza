<div class="list-params">
    <p>
     <span><b>Цена:</b> {{$service->sum_format}} руб</span>
    </p>
    <p>
        <span><b>Минимальный объём заказа:</b> {{$service->size}} </span>

    </p>
    <p>
        <span><b>Стоимость минимального заказа:</b> {{$service->sum_format}} руб.</span>

        {{--Нет данных--}}
    </p>
    <p>{{$service->text}}</p>
</div>