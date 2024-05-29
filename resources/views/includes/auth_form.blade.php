@guest
    <section class="post-header">

        <div class="auth" style="width: 100%;">
        @include('includes.auth_fields')
        </div>

    </section>
@endguest
@include('includes.youtube')
<div class="news-list">
    <h3 class="title">@lang('transbaza_home.news')</h3>
    @foreach($latest_news as $value)
        <div class="item" style="width: 100%; height: auto;">
            @if($value->image)
                <div class="image-wrap">
                    <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"><img
                                class="lazy" data-src="/{{$value->image}}" alt="{{$value->title}}"></a>
                </div>
            @endif
            <div class="content-wrap" style="    padding: 0px 15px 15px;">
                <p class="title" style="font-size: 14px;margin-top: 0px;">{{$value->preview_title}}</p>
                <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"
                   class="detail">@lang('transbaza_home.more')</a>
            </div>
        </div>
    @endforeach
</div>
<div class="news-list">
    <h3 class="title">@lang('transbaza_home.article_title')</h3>
    @foreach($random_articles as $value)
        <div class="item" style="width: 100%; height: auto;">
            @if($value->image)
                <div class="image-wrap">
                    <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"><img
                                class="lazy" data-src="/{{$value->image}}" alt="{{$value->title}}"></a>
                </div>
            @endif
            <div class="content-wrap" style="padding: 0px 15px 15px;">
                <p class="title" style="font-size: 14px;margin-top: 0px;">{{$value->preview_title}}</p>
                <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"
                   class="detail">@lang('transbaza_home.more')</a>
            </div>
        </div>
    @endforeach
</div>

{{--
<div class="news-list">
    <h3 class="title">Техника</h3>
    @foreach($random_machine as $machine)
        <div class="item" style="width: 100%; height: auto; text-align: center">
            @if($machine->photo)
                <div class="image-wrap">
                    <a href="{{$machine->rent_url}}"><img style="width: 100%"
                                src="/{{$machine->photos[0]}}" alt="{{$machine->name}}"></a>
                </div>
            @endif
         --}}
{{--   <div class="content-wrap">
                <p class="title">{{$value->preview_title}}</p>
                <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"
                   class="detail">подробнее</a>
            </div>--}}{{--

        </div>
    @endforeach
</div>
--}}

