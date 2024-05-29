<rss xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/"
     xmlns:turbo="http://turbo.yandex.ru" version="2.0">
    <channel>
        <title>{{ $global_options->where('key', 'main_title')->first()->value ?? ''}}</title>
        <link>
        https://trans-baza.ru</link>
        <description>{{$global_options->where('key', 'main_description')->first()->value ?? '' }} </description>
        <language>ru</language>
        @foreach($articles as $article)
            @php
                if($article->type === 'news'){
                $link_name = 'get_news_article';
                }elseif($article->type === 'article'){
                $link_name = 'get_article';
                }else{
                $link_name = 'article_index';
                }
               $content_link = str_replace(env('APP_ROUTE_URL'), 'trans-baza.ru', route($link_name, $article->alias));
            $content_link = str_replace('/content/', '/', $content_link);
            @endphp
            <item turbo="true">
                <link>{{$content_link}}</link>
                <turbo:content>
                    <![CDATA[
                    <header>
                        <h1>{{$article->h1}}</h1>
                        <menu>
                            <a href="https://trans-baza.ru">Главная</a>
                            <a href="https://trans-baza.ru/news">Новости</a>
                            <a href="https://trans-baza.ru/about">О системе</a>
                            <a href="https://trans-baza.ru/spectehnika">Каталог техники</a>
                        </menu>
                    </header>
                    {!! $article->content !!}
                    ]]>
                </turbo:content>
            </item>
        @endforeach
        <item turbo="true">
            <link>
            https://trans-baza.ru/spectehnika</link>
            <turbo:content>
                <![CDATA[
                <header>
                    <h1>{{trans('transbaza_spectehnika.catalog_h1')}}</h1>
                    <menu>
                        <a href="https://trans-baza.ru">Главная</a>
                        <a href="https://trans-baza.ru/news">Новости</a>
                        <a href="https://trans-baza.ru/about">О системе</a>
                        <a href="https://trans-baza.ru/spectehnika">Каталог техники</a>
                        {{--   @foreach($global_static_contents as $content)
                           <a href="{{route('article_index', $content->alias)}}">{{$content->menu_title}}</a>
                           @endforeach--}}
                    </menu>
                </header>
                @foreach(\App\Machines\Type::query()->forDomain()->get() as $category)
                    @if(!$category->photo)
                        @continue
                    @endif

                        <div class="image">
                            <img src="{{$category->thumbnail_link}}"/>
                        </div>
                        <button
                                formaction="{{str_replace(env('APP_ROUTE_URL'), 'trans-baza.ru', route('directory_main_category', $category->alias))}}"
                                data-background-color="#f37153"
                                data-color="white"
                                data-turbo="false"
                                data-primary="true">{{$category->name}} ({{$category->machines()->count()}})
                        </button>
                @endforeach
                ]]>
            </turbo:content>
        </item>
    </channel>
</rss>