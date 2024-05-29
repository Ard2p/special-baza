<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('header', View::make('layouts.head', isset($article) ?  ['article' => $article] : []))
    {{--{!! $global_options->where('key', 'analytics_head')->first()->value ?? '' !!}--}}


    @if(isInPlaceEditing(1))
        <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap3-editable/css/bootstrap-editable.css"
              rel="stylesheet"/>
        <link href="/vendor/laravel-translation-manager/css/translations.css" rel="stylesheet">
    @endif

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#ffc40d">
    <meta name="theme-color" content="#e44621">

    <link rel="stylesheet" href="{{mix('/css/theme/style.css')}}">
</head>
<body>

{{--{!! $global_options->where('key', 'analytics_body')->first()->value !!}--}}

<header>
    <div class="header-wrap">
        <div class="logo">
            {{--<a href="{{\Request::route()->getName() == 'index' ? '#' : '/' }}">
                <img src="/img/logos/logo-tb-eng-g-200.png" class="full" alt="">
                <img src="/img/logos/small.png" class="small" alt="">
            </a>--}}
            @if(\Request::route()->getName() == 'index')

                <img src="/img/logos/logo-tb-eng-g-200.png" class="full" alt="" style="margin-top: 10px;">
                <img src="/img/logos/small.png" class="small" alt="">
            @else
                <a href="/">
                    <img src="/img/logos/logo-tb-eng-g-200.png" class="full" alt="">
                    <img src="/img/logos/small.png" class="small" alt="">
                </a>
            @endif
        </div>
        <nav>
            @auth
                <div class="user-profile">
                    <div class="head">
                        {{--<a href="/support" class="feedback {{Auth::user()->checkUnreadMessages()?  'active' : ''}}"></a>--}}
                        <a href="/{{Auth::user()->getCurrentRoleName()}}/dashboard" class="user-icon">
                            <span class="name">{!! Auth::user()->email !!}</span></a>
                    </div>
                    <div class="bottom">
                        <a href="/{{Auth::user()->getCurrentRoleName()}}/balance" class="finance">
                            <div class="finance_"
                                 style="margin-right: 7px;">{{Auth::user()->getBalance('widget') / 100}} </div>
                            <span class="currency"> Руб</span></a>
                    </div>
                </div>
                <div class="user-role">
                    {{--if user has only one role--}}
                    @if((!Auth::user()->checkRole(['customer']) || !Auth::user()->checkRole(['performer'])) && (!Auth::user()->checkRole(['admin']) && !Auth::user()->isContentAdmin()))
                        <div class="single-role">
                            @php
                                $role = Auth::user()->getCurrentRoleName();
                                $role_name = 'Виджет';
                            @endphp
                            <a href="{{route('profile_index')}}">{{$role_name}}</a>
                        </div>
                        {{--if user has multi roles--}}
                    @elseif(Auth::user()->checkRole(['performer', 'customer']) || (Auth::user()->checkRole(['admin']) || Auth::user()->isContentAdmin()))
                        <div class="form-item">
                            <div class="custom-select-exp">
                                <select name="change_role">
                                    <option value="">Выберите роль</option>
                                    @if(Auth::user()->checkRole(['customer']))
                                        <option value="customer" selected>
                                            @lang('transbaza_roles.customer')
                                        </option> @endif
                                    @if(Auth::user()->checkRole(['performer']))
                                        <option value="contractor">
                                            @lang('transbaza_roles.contractor')
                                        </option>
                                    @endif
                                    @if(Auth::user()->checkRole(['widget']))
                                        <option value="widget" >      @lang('transbaza_roles.widget')
                                        </option>
                                    @endif
                                    @if(Auth::user()->checkRole(['admin']) || Auth::user()->isContentAdmin())
                                        <option value="admin">      @lang('transbaza_roles.admin')
                                        </option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="user-menu">
                    <a href="#" class="burger"></a>
                    <!--show menu? add class active-->
                    <div class="menu-list">
                        <ul>
                            <li><a href="{{route('profile_index')}}">Профиль</a></li>
                            <li>
                                <a href="{{route('widget_finance')}}">Мои финансы</a></li>
                            <li><a href="{{ route('my-services.index') }}">Мои Услуги</a></li>
                            <li><a href="{{ route('adverts.index') }}">Мои Объявления</a></li>
                            <li><a href="{{env('APP_URL')}}/{{Auth::user()->getCurrentRoleName()}}/documents">Документы</a></li>
                            <li><a href="{{route('support.index')}}">Поддержка</a></li>
                            <li><a href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">Выход</a></li>
                        </ul>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            @endauth
        </nav>
        <div class="mobile-menu">
            @auth
                <div class="user-role mobile-view">
                    <div class="form-item">
                        <div class="custom-select-exp">
                            <select name="change_role">
                                <option>Выберите роль</option>
                                @if(Auth::user()->checkRole('performer'))
                                    <option value="contractor">
                                        @lang('transbaza_roles.contractor')
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('customer'))
                                    <option value="customer" selected>   @lang('transbaza_roles.customer')
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('widget'))
                                    <option value="widget">   @lang('transbaza_roles.widget')
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('admin'))
                                    <option value="admin">   @lang('transbaza_roles.admin')
                                    </option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            @endauth
            @guest
                <div class="auth-btn button">
                    <a href="/login" class="btn-custom">Войти</a>
                </div>
            @endguest
            <div class="hamburger hamburger--3dx">
                <div class="hamburger-box">
                    <div class="hamburger-inner"></div>
                </div>
            </div>
        {{--<a href="#" class="burger"></a>--}}
        <!--show menu? add class active-->
            <div class="menu-list in_top">
                <ul>
                    @auth
                        <li><a href="{{route('profile_index')}}">Профиль</a></li>
                        {{--  <li><a href="/{{Auth::user()->getCurrentRoleName()}}/requisites">Реквизиты</a></li>--}}
                        {{--   <li><a href="/{{Auth::user()->getCurrentRoleName()}}/payments">Платежи</a></li>--}}
                        <li>
                            <a href="{{route('widget_finance')}}">Мои финансы</a></li>
                        <li><a href="{{ route('my-services.index') }}">Мои Услуги</a></li>
                        <li><a href="{{ route('adverts.index') }}">Мои Объявления</a></li>
                        <li><a href="/documents">Документы</a></li>
                        <li><a href="/support">Поддержка</a></li>
                        <li><a href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">Выход</a></li>
                        <li class="user">
                            <div class="user-profile">
                                <a href="/support"
                                   class="feedback {{Auth::user()->checkUnreadMessages()?  'active' : ''}}"></a>
                                <a href="/{{Auth::user()->getCurrentRoleName()}}/dashboard" class="name">
                                    <span class="name">{!! strlen(Auth::user()->email) > 10 ? substr(Auth::user()->email, 0, 10) . '... ' :  Auth::user()->email!!}</span></a>
                                {{--<a href="/{{Auth::user()->getCurrentRoleName()}}/balance">{{Auth::user()->getCurrentBalance() / 100}} <span--}}
                                {{--class="currency">Руб</span></a>--}}
                            </div>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </div>
</header>


@if((\Request::route()->getName() == 'index'))

    <section class="post-header">

        <div class="slider {{\Auth::check() ? 'full-width' : ''}}">
            @php
                $slider  = $global_options->where('key', 'slider')->first();
            $i = 0;
            @endphp
            @if($slider)
                @foreach(json_decode($slider->value) as $slide)
                    @php
                        ++$i;
                        if($i !== 7){
                        continue;
                        }
                    @endphp
                    <div class="item">
                        <a href="{{route('register')}}"><img class="lazy" data-src="/{{$slide}}" alt=""></a>
                    </div>
                @endforeach
            @endif
        </div>
        @guest
            @if((\Request::route()->getName() !== 'register') && !Request::is('password/*'))
                <div class="auth">
                    <form action="post" id="auth_form">
                        @csrf
                        <h2 class="title">@lang('transbaza_login.title')</h2>
                        <div class="form-item image-item">
                            <label for="">
                                @lang('transbaza_menu.your_email')
                                <input type="email" name="email" placeholder="@lang('transbaza_menu.type_email')">
                                <span class="image email"></span>
                            </label>
                            <span class="error"></span>
                        </div>
                        <div class="form-item image-item">
                            <label for="">
                                @lang('transbaza_menu.your_password')
                                <input type="password" name="password"
                                       placeholder="@lang('transbaza_menu.your_password')">
                                <span class="image lock"></span>
                            </label>
                        </div>
                        <div class="button">
                            <button type="submit" class="btn-custom">@lang('transbaza_menu.enter')</button>
                        </div>
                    </form>
                    <hr>
                    <a href="/password/reset" class="link-register"><span
                                class="red">@lang('transbaza_menu.forget_password')</span></a>
                    <div class="button">
                        <a href="/register" class="btn-custom black">@lang('transbaza_menu.register')</a>
                    </div>
                </div>
            @endif
        @endguest
    </section>
@endif

<section class="content {{(\Request::route()->getName() == 'index') ? 'main-page' : ''}}" id="app">

    @yield('content')
</section>
@auth

        <div class="visible-sm visible-xs">
            @include('includes.switch_lng')
        </div>

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

{{--@if(($mes = \App\Modules\LiveChat\ChatMessage::orderBy('created_at', 'desc')->get()) && ((Auth::check()  && Auth::user()->enable_ticker) || !Auth::check()))
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
@endif--}}

<footer>
    <div class="footer-wrap">
        <div class="logo">
            <a href="#"><img src="/img/logos/logo2.png" alt=""></a>
        </div>
        <nav>
            @foreach($global_static_contents as $content)
                <a href="{{env('APP_URL')}}/{{$content->alias}}">{{$content->menu_title}}</a> </li>
            @endforeach
         {{--   <a href="{{env('APP_URL')}}/faq">FAQ</a></li>--}}
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
                            <a href="{{env('APP_URL')}}{{$content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty() ? '#' : "/{$content->alias}"}}">{{$content->menu_title}}</a>
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
                 {{--   <li class="has-sub-m"><a href="{{env('APP_URL')}}/faq">FAQ</a>
                    </li>--}}

                    @guest
                        <li><a href="/register">@lang('transbaza_menu.register')</a></li>
                    @endguest

                </ul>
            </div>
        </div>
    </div>
</footer>
<div class="absolute-width"></div>
{!! \App\Proposal\SimpleProposal::renderDefault($machine ?? null) !!}
@ticketPopup
<!--js here-->
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
    @if(isInPlaceEditing(1))
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
        <script src="/vendor/laravel-translation-manager/js/inflection.js"></script>
        <script src="/vendor/laravel-translation-manager/js/translations.js"></script>
    @endif
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

        $('.slider').slick({
            dots: false,
            autoplay: true,
            autoplaySpeed: '{{ $global_options->where('key', 'slider_delay')->first()->value * 1000 ?? 5000 }}',
            prevArrow: false,
            nextArrow: false
        });

        $('#__live').html("<a href='//www.liveinternet.ru/click' " +
            "target=_blank><img src='{{route('get_live_counter')}}' alt='' title='LiveInternet: показано число посетителей за" +
            " сегодня' " +
            "border='0' width='88' height='15'><\/a>")
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