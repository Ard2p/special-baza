@foreach($collection as $item)
    <p class="h5">Ссылка <span class="h6">{{$item->link}}</span></p>
    <p class="h5">Отправлена на <span class="h6">{{$item instanceof \App\Marketing\SmsLink
    ? $item->friend->phone_format
    : $item->friend->email
    }}</span></p>
    <p class="h5">Польователь <span class="h6">{{$item->friend->user->id_with_email}}</span></p>
    @if($item instanceof \App\Marketing\SmsLink)
        <p class="h5">Доставлено <span class="h6">{!!  $item->is_watch ? 'Да' : 'Нет' !!}</span></p>
    @else
        <p class="h5">Просмотрено <span class="h6">{!!  $item->is_watch ? 'Да' : 'Нет' !!}</span></p>
        <p class="h5">Дата просмотра <span class="h6">{{$item->watch_at}}</span></p>
    @endif
    <p class="h5">Клик <span class="h6">@switch($item->confirm_status)
                @case(0)
                @break
                @case(1)
                Да
                @break
                @case(2)
                Нет
                @break
            @endswitch </span></p>
    <p class="h5">Дата клика: <span class="h6">{{$item->confirm_at}}</span></p>
    <hr>
@endforeach