<!doctype html>
<html  lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="yandex-verification" content="0786b0ac9fb3c48b" />
    @yield('header', View::make('layouts.head', isset($article) ?  ['article' => $article] : []))
    {{--{!! $global_options->where('key', 'analytics_head')->first()->value ?? '' !!}--}}
    @if(isInPlaceEditing(1))
        <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap3-editable/css/bootstrap-editable.css"
              rel="stylesheet"/>
        <link href="/vendor/laravel-translation-manager/css/translations.css" rel="stylesheet">
    @endif
    <meta name="theme-color" content="#e44621">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#ffc40d">

    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <script src="//code.jivosite.com/widget.js" data-jv-id="tsPA7jIdLq" async></script>
    <link rel="stylesheet" href="{{('/css/theme/style.css')}}">
</head>
<body>

{{--{!! $global_options->where('key', 'analytics_body')->first()->value !!}--}}
<!------ Include the above in your HEAD tag ---------->

{{--
@if(Request::filled('__webwidget'))

    @include('includes.main.widget_header')

@else

    @include('includes.main.header')
@endif
--}}


@if((\Request::route()->getName() == 'index'))
    {{--<div class="main-title">--}}
    {{--<h1>--}}
    {{--Сервис #1 по СРОЧНОМУ заказу ЛЮБОЙ техники со сроком «на завтра», с гарантией прибытия техники на объект--}}
    {{--</h1>--}}
    {{--</div>--}}
    <section class="post-header">

        {{-- <div  class="slider-h1">
        <h1>Онлайн платформа по аренде/покупке/продаже <br> оборудования и коммерческого транспорта</h1>
         </div>
        <h2 class="slider-h2">Срочный заказ спецтехники. <br>Работа для профессионалов.</h2>--}}
       @guest
        <div class=" full-width {{\Auth::check() ? 'full-width' : ''}}">

            <div class=" slick-initialized slick-slider">
                <div class="col-md-12 text-sm-center">
                <h1 style="font-size: 5vw;">TRANSBAZA</h1>
                <h2 class="h2-index">@lang('transbaza_index.main_h2')</h2>
                </div>
                <div class="col-md-12 row">
                    <div class="col-sm-4 col-sm-12 ">
                        <div class="button" style="padding-top: 10px">
                            <a href="{{route('register')}}" class="btn-custom" style="background: #ec4925; border-color: #ec4925">
                                @lang('transbaza_index.get_started')
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-4 col-sm-12">
                        <div class="button" style="padding-top: 10px">
                            <a href="/about" class="btn-custom black">
                                @lang('transbaza_index.more_info')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endguest
            {{--      @php
                      $slider  = $global_options->where('key', 'slider')->first();
                  $i = 0;
                  @endphp
                  @if($slider)
                      @foreach(json_decode($slider->value) as $slide)
                          @php
                              ++$i;
                              if($i !== 8){
                              continue;
                              }
                          @endphp
                          <div class="item">
                              <a href="{{route('register')}}"><img class="lazy" data-src="/{{$slide}}" alt=""></a>
                          </div>
                      @endforeach
                  @endif--}}
        </div>


        {{-- @guest
             @if((\Request::route()->getName() !== 'register') && !Request::is('password/*'))
                 <div class="auth">
                @include('includes.auth_fields')
                 </div>
             @endif
         @endguest--}}
    </section>
@endif

<section class="content {{(\Request::route()->getName() == 'index') ? 'main-page' : ''}}" id="app">

    @yield('content')
</section>
@auth

@endauth
<div class="modal-t" id="errorsModal">
    <div class="overlay">
        <div class="popup-data">
            <div class="head">
                {{-- <p class="small">Информация !</p>--}}
            </div>
            <div class="main">
                <h2></h2>
            </div>
            <div class="footer">
                <div class="button">
                    <a href="#" class="btn-custom">ok</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{--
@if(($mes = \App\Modules\LiveChat\ChatMessage::orderBy('created_at', 'desc')->get()) && ((Auth::check()  && Auth::user()->enable_ticker) || !Auth::check()))
    <a href="{{route('chat_index')}}" id="ticker_line">
        <div class="ticker-wrap">
            <div class="ticker">
                @foreach($mes as $msg)
                    <div class="ticker__item">#{{$msg->user_id}}: {{$msg->message}}</div>
                @endforeach
            </div>
            @if(Auth::check())
                <a href="#" id="close_ticker" data-url="{{route('close_ticker')}}"
                   style=" position: fixed;  bottom: -3px;right: 0px;width: 37px;color: white;font-size: 30px;background: black;">
                    <i class="fa fa-window-close" aria-hidden="true"></i>
                </a>
            @endif
        </div>

    </a>
@endif
--}}

{{--<footer>
    <div class="footer-wrap">
        <div class="logo">
            <a href="#"><img src="/img/logos/logo2.png" alt=""></a>
        </div>
        <nav>
            @foreach($global_static_contents as $content)
                <a href="{{env('APP_URL')}}/{{$content->alias}}">{{$content->menu_title}}</a> </li>
            @endforeach
            --}}{{--    <a href="{{env('APP_URL')}}/faq">FAQ</a></li>--}}{{--
            @guest
                <a href="/register">@lang('transbaza_menu.register')</a>
            @endguest
        </nav>
        <div class="mobile-menu">

            <div class="hamburger hamburger--3dx">
                <div class="hamburger-box">
                    <div class="hamburger-inner"></div>
                </div>
            </div>

            <div class="menu-list in_footer">
                <ul>

                    @foreach($global_static_contents as $content)
                        <li class="{{$content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty()  ? 'has-sub-m' : ''}}">
                            <a href="{{$content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty() ? 'javascript:;' : env('APP_URL') . "/{$content->alias}"}}">{{$content->menu_title}}</a>
                            @if($content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty() )
                                <ul class="ul-hide">
                                    <li><a href="{{env('APP_URL')}}/{{$content->alias}}">{{$content->menu_title}}</a>
                                    </li>
                                    @foreach($content->subMenuArticlesSections as $sub_sec)
                                        <li><a href="{{env('APP_URL')}}/{{$sub_sec->alias}}">{{$sub_sec->name}}</a></li>
                                    @endforeach
                                    @foreach($content->subMenuArticles as $sub)
                                        <li><a href="{{env('APP_URL')}}/{{$sub->alias}}">{{$sub->title}}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                    --}}{{--  <li class="has-sub-m"><a href="{{env('APP_URL')}}/faq">FAQ</a>
                      </li>--}}{{--
                    --}}{{--<li><a href="/for-customer">Заказчику</a></li>
                    <li><a href="/for-contractor">Исполнителю</a></li>
                    <li><a href="/for-partner">Партнеру</a></li>--}}{{--
                    @guest
                        <li><a href="/register">@lang('transbaza_menu.register')</a></li>
                    @endguest

                </ul>
            </div>
        </div>
    </div>
</footer>--}}
<div class="absolute-width"></div>
{{--{!! \App\Proposal\SimpleProposal::renderDefault($machine ?? null) !!}--}}
{{--@ticketPopup--}}
<!--js here-->
<script src="{{route('assets.lang')}}"></script>
@if(preg_match('~MSIE|Internet Explorer~i', ($_SERVER['HTTP_USER_AGENT'] ?? '')) || (strpos(($_SERVER['HTTP_USER_AGENT'] ?? ''), 'Trident/7.0; rv:11.0') !== false))
    <script src="{{('/js/appEs5.js')}}"></script>
@else

    <script src="{{('/js/app.js')}}"></script>
@endif

{{--<script src="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.js"></script>--}}


{{--<script src="/js/select/bootstrap-select.js"></script>--}}


{{--<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css"
      media="screen">--}}
<noindex>
    @push('after-scripts')
        <script>

            function createSlider()
            {
                $(".main-slider").slick({
                    centerMode: true,
                    speed: 300,
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    variableWidth: true,
                    dots: true,
                    adaptiveHeight: true
                });

                $(".fancybox").fancybox({
                    openEffect: "none",
                    closeEffect: "none"
                });
            }
            function createSliderOnElement(element)
            {
                element.find(".main-slider").slick({
                    centerMode: true,
                    speed: 300,
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    variableWidth: true,
                    dots: true,
                    adaptiveHeight: true
                });

                $(".fancybox").fancybox({
                    openEffect: "none",
                    closeEffect: "none"
                });
            }
            $(document).ready(function () {
                createSlider();
            })

        </script>
        <style>
            .slick-slide {
                margin: 0 36px;
            }

            .slick-list {
                margin: 0 -10px;
            }
        </style>
    @endpush
    @if(isInPlaceEditing(1))
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
        <script src="/vendor/laravel-translation-manager/js/inflection.js"></script>
        <script src="/vendor/laravel-translation-manager/js/translations.js"></script>
    @endif
    @push('after-scripts')
        <style>
            .col-md-4:nth-child(3n+1) {
                clear: left;
            }
        </style>
    @endpush
    <script type="text/javascript">
        var head_text =
                {!! json_encode(['text' => $global_options->where('key', 'analytics_head')->first()->value]) !!}
        var head_body =
                {!! json_encode(['text' => $global_options->where('key', 'analytics_body')->first()->value]) !!}

        var current_redirect = '{{(env('APP_ADMIN_SUBDOMAIN')
        ? 'office.' . env('APP_ROUTE_URL')
        : (env('APP_ROUTE_URL') . '/admin') ) }}';
        var widget_home = '{{route('home_widget')}}';
        var transbaza_url = '{{env('APP_URL')}}';

        $('head').append(head_text.text)
        $('body').append(head_body.text)

        {{--  $('.slider').slick({
            dots: false,
            autoplay: true,
            autoplaySpeed: '{{ $global_options->where('key', 'slider_delay')->first()->value * 1000 ?? 5000 }}',
            prevArrow: false,
            nextArrow: false
        }); --}}

        $('#__live').html("<a href='//www.liveinternet.ru/click' " +
            "target=_blank><img src='{{route('get_live_counter')}}' alt='' title='LiveInternet: показано число посетителей за" +
            " сегодня' " +
            "border='0' width='88' height='15'><\/a>")


        function checkJivoOffset() {
            if ($('body #jvlabelWrap').offset().top + $('body #jvlabelWrap').height()
                >= $('footer').offset().top - 10) {
                console.log(2);
                $(document).find('#jvlabelWrap').css('margin-bottom', '60px');

            }

            if ($(document).scrollTop() + window.innerHeight < $('footer').offset().top) {
                console.log(1);
                $('body #jvlabelWrap').css('margin-bottom', 'unset');
            }
        }
        $(document).scroll(function () {
            if ($('body #jvlabelWrap').length) {
                checkJivoOffset();
            }
        });

    </script><!--/LiveInternet-->
    @stack('after-scripts')
    {!! NoCaptcha::renderJs() !!}

</noindex>
<div style="display: none">
    <!--LiveInternet counter-->
    <script type="text/javascript">
        document.write("<a href='//www.liveinternet.ru/click' " +
            "target=_blank><img src='//counter.yadro.ru/hit?t26.6;r" +
            escape(document.referrer) + ((typeof (screen) == "undefined") ? "" :
                ";s" + screen.width + "" + screen.height + "" + (screen.colorDepth ?
                screen.colorDepth : screen.pixelDepth)) + ";u" + escape(document.URL) +
            ";h" + escape(document.title.substring(0, 150)) + ";" + Math.random() +
            "' alt='' title='LiveInternet: показано число посетителей за" +
            " сегодня' " +
            "border='0' width='88' height='15'><\/a>")
    </script><!--/LiveInternet-->
    @stack('styles')
</div>

</body>
</html>