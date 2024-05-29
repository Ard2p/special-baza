@extends('layouts.main')
@section('header')
    <title>{{$article->title}}</title>
    <meta name="description"
          content="{{$article->description}}">
    <meta name="keywords"
          content="{{$article->keywords}}">

    <meta property="og:title" content="{{$article->title}}"/>
    <meta property="og:url" content="{{request()->fullUrl()}}"/>
    <meta property="og:type" content="article"/>
    <meta property="og:description" content="{{$article->description}}"/>
    @if($article->image)
        <meta property="og:image" content="http://{{ env('APP_ROUTE_URL') . '/' .($article->image)}}"/>
        <meta property="og:image:secure_url" content="{{ url($article->image)}}"/>
    @endif

@endsection
@section('content')
    <div class="container article-wrap">
        @if($article->is_static === 0)
            <ol class="breadcrumb">
                <li><a href="{{route('index')}}">@lang('transbaza_home.breadcrumb_home')</a></li>
                <li>
                    <a href="{{$article->is_news ? '/news' : '/articles'}}">{{$article->is_news ? trans('transbaza_home.breadcrumb_news') : trans('transbaza_home.breadcrumb_article')}}</a>
                </li>
                <li class="active">{{$article->title}}</li>
            </ol>
        @endif
        <div itemscope itemtype="http://schema.org/Article"
             class="  @guest col-md-9 col-md-push-3 @endguest col-sm-12 ">
            @seoTop
            @contactTop

            @isset($article->created_at)
                <meta itemprop="datePublished" content="{{$article->created_at->format('Y-m-d') ?? ''}}">

                <!-- Дата последнего изменения статьи -->
                <meta itemprop="dateModified" content="{{$article->updated_at->format('Y-m-d') ?? ''}}">

            @endisset
            <meta itemprop="description" content="{{$article->description}}">

            <meta itemprop="author" content="transbaza">


            <!-- blah blah -->
            <div itemprop="publisher" itemscope itemtype="https://schema.org/Organization" style="display: none">
                <div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
                    <img src="https://trans-baza.ru/img/logos/logo-tb-eng-g-200.png" itemprop="contentUrl"/>
                </div>
                <meta itemprop="name" content="TRANS-BAZA.RU">
                <meta itemprop="telephone" content="+7(495)975-75-28">
                <meta itemprop="address" content="г. Москва, ул. Островная д.2, подъезд №2, офис 248">

            </div>


            <div class="content" itemprop="articleBody">
                @if($article->h1) <h1 itemprop="headline">{{$article->h1}}</h1> @endif
                {{-- @if($article->image)
                     <div class="background-image" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                         <img itemprop="image url" src="/{{$article->image}}" alt="{{$article->image_alt ?: $article->h1}}">
                     </div>
                 @endif--}}
                {!!  html_entity_decode($article->content) !!}
                <div class="clearfix"></div>
                @if($article->created_at) <span
                        class="h-25 float-right">{{$article->created_at->format('d.m.Y')}}</span> @endif
            </div>
            @if($article->alias === 'map')
                @include('includes.g_map')
                @yield('map')
            @endif
            @if($article instanceof \App\Content\StaticContent && $article->id === 7)
                {!!  Route::dispatch(Request::create(route('get_stats_index')))->content()!!}
            @endif
            @seoBottom
            @contactBottom
            <div class="clearfix"></div>
            {!! \App\Marketing\ShareList::renderShare() !!}
        </div>
        @guest
            <div class="col-md-3    col-md-pull-9">
                <section class="post-header">
                    <div class="auth" style="width: 100%;">
                        @include('includes.auth_fields')
                    </div>

                </section>
                @include('includes.youtube')
            </div>
        @endguest
    </div>

@endsection
