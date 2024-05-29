@extends('layouts.main')

@section('content')

    <div class="news-list">
        <div class="item card-call">
            <div class="image-wrap">
                <a href="{{route('directory_main')}}"><img class="lazy" data-src="/images/1_{{App::getLocale()}}.png"></a>
            </div>
            <div class="content-wrap">
                <p class="title">@lang('transbaza_index.card_1_title')</p>
                <p class="description">@lang('transbaza_index.card_1_description')</p>
                <div class="button">

                        <a href="{{route('directory_main')}}" class="btn-custom">@lang('transbaza_home.order_machineries') <i class="arrow-right"></i></a>

                </div>
            </div>
        </div>
        <div class="item card-call">
            <div class="image-wrap">
                <a href="{{route('order.index')}}"><img class="lazy" data-src="/images/2_{{App::getLocale()}}.png"></a>
            </div>
            <div class="content-wrap"><p class="title">@lang('transbaza_index.card_2_title')</p>
                <p class="description">@lang('transbaza_index.card_2_description')</p>
                <div class="button">

                        <a href="{{route('order.index')}}" class="btn-custom">@lang('transbaza_home.find_orders') <i class="arrow-right"></i></a>

                </div>
            </div>
        </div>
    </div>
    <div style="  ">
    <h3 class="text-center">@lang('transbaza_index.about_us')</h3>
    <p style="text-align: justify; padding: 10px;" >
        @lang('transbaza_index.project_description')
    </p>


            <div class="payment-png text-center form-inline">
                <p>@lang('transbaza_home.join_social'):</p>
                <span>
                        <a href="https://www.facebook.com/transbaza/"><img class="lazy" data-src="/images/social/fb.png"></a>
                        <a href="https://vk.com/transbazaru"><img class="lazy" data-src="/images/social/vk.png"></a>
                        <a href="https://www.youtube.com/c/transbaza"><img
                                    class="lazy" data-src="/images/social/youtube.png"></a>
                        <a href="https://www.instagram.com/transbaza/ "><img class="lazy" data-src="/images/social/insta.png"></a>
                        <a href="https://www.linkedin.com/company/transbaza/ "><img class="lazy" style="    padding: 7px;" data-src="/images/social/linked.png"></a>
                    </span>
            </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class=" payment-png text-center"><span class="h4" style="white-space: nowrap;"><a class="h4"
                                                                                                   href="/usloviya-oplati"
                                                                                                   title="Условия оплаты">@lang('transbaza_home.payment_methods'):</a> </span>
                <span style="display: inline-block;">
                        <img class="lazy" data-src="/images/cards/MIRaccept.png" class="image">
                        <img class="lazy" data-src="/images/cards/_Visa.png">
                        <img class="lazy" data-src="/images/cards/_verified-by-visa.png">
                        <img class="lazy" data-src="/images/cards/Mastercard-logo.svg.png">
                        <img class="lazy" data-src="/images/cards/_mastercard-securecode.png"></span>
            </div>
        </div>
    </div>
    {!! $global_options->where('key', 'custom_index_html')->first()->value !!}
    @if($articles->count())
        <div class="news-list" style="margin-top: 40px;">
            <h2 class="title">@lang('transbaza_home.news')</h2>
            @foreach($articles as $value)
                <div class="item">
                    @if($value->image)
                        <div class="image-wrap">
                            <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"><img class="lazy" data-src="/{{$value->image}}"
                                                                                         alt="{{$value->title}}"></a>
                        </div>
                    @endif
                    <div class="content-wrap">
                        <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}"
                           class="title no-decoration">{{$value->title}}</a>

                        <p class="description">{{$value->description}}</p>

                        <a href="{{$value->is_news ? route('get_news_article', $value->alias) :  route('get_article', $value->alias) }}" class="detail">@lang('transbaza_home.more')</a>
                        <span class="h-25 float-right">{{$value->created_at->format('d.m.Y')}}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @guest
        {!! \App\Marketing\ShareList::renderShare() !!}
    @endguest
@endsection

