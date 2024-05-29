@if($links->isNotEmpty())
    <ul>
        @foreach($links as $link)

            <li><a target="_blank" href="/seo_contents/{{$link->id}}/edit">{{$link->city->region->name ?? ''}}, {{$link->city->name ?? ''}}</a></li>

        @endforeach
    </ul>
@else
    Телефон не найден. Возможно уже удален либо необходимо использовать ручной поиск.
@endif