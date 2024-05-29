{{--<div class="col-md-12">
<div class="row">
@foreach($articles as $value)

    <div class="col-md-6 col-lg-4">
        <div class="panel">
            <div class="panel-body">
                @if($value->image)
                    <div class="image-wrap" >
                        <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"><img style="    max-height: 200px;width: 100%;"
                                    src="/{{$value->image}}" alt="{{$value->title}}"></a>
                    </div>
                @endif
                <div class="content-wrap">
                    <p class="title" style="color: #363636;
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 10px;">{{$value->preview_title}}</p>
                    <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"
                       class="detail" style="    color: #363636;
    font-size: 14px;
    font-weight: 700;
    line-height: 24px;
    text-transform: uppercase;">подробнее</a>
                </div>
            </div>
        </div>
    </div>
@endforeach
    </div>
    </div>
<div class="clearfix"></div>--}}
@foreach($articles as $value)
    <div class="item">
        @if($value->image)
            <div class="image-wrap" style="max-height: 200px">
                <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"><img src="/{{$value->image}}" alt="{{$value->title}}"></a>
            </div>
        @endif
        <div class="content-wrap">
            <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}" class="title  no-decoration" >{{$value->preview_title}}</a>

            <p style="    margin-top: 10px;">
                <span class="h-25 float-right">{{$value->created_at->format('d.m.Y')}}</span>

                <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}" class="detail">@lang('transbaza_home.more')</a></p>
        </div>
    </div>
@endforeach
