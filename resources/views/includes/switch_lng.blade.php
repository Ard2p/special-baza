@if(Auth::user()->checkRole(['admin']) || Auth::user()->checkRole(['content_admin']))
    <ul class="list-inline">
        <li>
            <form method="POST" action="https://trans-baza.ru/change-lng-mode">
                @csrf
                <button class="btn" href="#" rel="ru-RU"><i class="fa fa-edit"></i></button>
            </form>
        </li>
       {{-- <li>
            <form method="POST" action="{{route('change_language', 'ru')}}">
                @csrf
                <button class="btn" href="#" rel="ru-RU"><img style="height: 30px; border: 1px solid"
                                                              src="https://flags.fmcdn.net/data/flags/w580/ru.png"
                                                              alt="РУССКИЙ"></button>
            </form>
        </li>
        <li>
            <form method="POST" action="{{route('change_language', 'en')}}">
                @csrf
                <button class="btn" href="#" rel="en-US"><img style="height: 30px"
                                                              src="data:image/png;base64,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"
                                                              alt="English"></button>
            </form>
        </li>--}}

    </ul>
@endif