<header>
    <div class="header-wrap" style="position: relative">
        <div class="logo">

            {{--<a href="{{\Request::route()->getName() == 'index' ? '#' : '/' }}">
                <img src="/img/logos/logo-tb-eng-g-200.png" class="full" alt="">
                <img src="/img/logos/small.png" class="small" alt="">
            </a>--}}
            @if(\Request::route()->getName() == 'index')

                <img class="lazy full" data-src="/img/logos/logo-tb-eng-g-200.png" alt="" style="margin-top: 10px;">
                <img class="small lazy" data-src="/img/logos/small.png" alt="">
            @else
                <a href="{{url('/')}}">
                    <img class="lazy full" data-src="/img/logos/logo-tb-eng-g-200.png"  alt="">
                    <img class="lazy small" data-src="/img/logos/small.png" alt="">
                </a>
            @endif
        </div>

        <nav>
            <ul class="list-inline">{{-- style="/*display: contents;*/position: relative;display: inherit;"--}}
                @guest
                    @foreach($global_static_contents as $content)
                        <li class="{{$content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty() ? 'has-sub' : ''}}">
                            <a href="{{env('APP_URL')}}/{{$content->alias}}"
                               class="{{Request::path() === $content->alias || Request::is($content->alias . '/*') ? 'active' : ''}}">{{$content->menu_title}}</a>
                            @if($content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty())
                                <div class="menu-sub">
                                    <ul>
                                        @foreach($content->subMenuArticlesSections as $sub_sec)
                                            <li><a href="{{env('APP_URL')}}/{{$sub_sec->alias}}">{{$sub_sec->name}}</a>
                                            </li>
                                        @endforeach
                                        @foreach($content->subMenuArticles as $sub)
                                            @php
                                                if($sub->is_news){
                                                  $route = route('get_news_article', $sub->alias);
                                                }elseif($sub->is_article) {
                                                $route = route('get_article', $sub->alias);
                                                }else {
                                                     $route = route('article_index', $sub->alias);
                                                }
                                            @endphp
                                            <li><a href="{{$route}}">{{$sub->title}}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                  {{--  <li><a href="{{env('APP_URL')}}/faq" class="{{Request::path() === 'faq' ? 'active' : ''}}">FAQ</a>
                    </li>--}}

                    <li><a href="{{route('login')}}" class="{{Request::path() === 'login' ? 'active' : ''}}">@lang('transbaza_login.enter')</a>
                    </li>
                @endguest
            </ul>
            @auth
                {{-- @performer <a href="/proposals">Заявки</a> @endPerformer
                 @customer <a href="/search">Поиск исполнителя</a> @endCustomer--}}

                <div class="user-profile">
                    <div class="head">
                        {{--<a href="/support" class="feedback {{Auth::user()->checkUnreadMessages()?  'active' : ''}}"></a>--}}
                        <a href="/{{Auth::user()->getCurrentRoleName()}}/dashboard" class="user-icon">
                            <span class="name">{!! Auth::user()->email !!}</span></a>
                    </div>
                    <div class="bottom">
                        <a href="/{{Auth::user()->getCurrentRoleName()}}/balance" class="finance">
                            <div class="finance_"
                                 style="margin-right: 7px;">{{Auth::user()->getCurrentBalance(true)}} </div>
                            <span class="currency"> Руб</span></a>
                    </div>

                </div>

                    @include('includes.switch_lng')

                <div class="user-role">
                    {{--if user has only one role--}}
                    @if((!Auth::user()->checkRole(['customer']) || !Auth::user()->checkRole(['performer'])) && (!Auth::user()->checkRole(['admin']) && !Auth::user()->isContentAdmin()))
                        <div class="single-role">
                            @php
                                $role = Auth::user()->getCurrentRoleName();
                                $role_name = !$role
                                ? trans('transbaza_roles.choose_role') : $role === 'contractor' ? trans('transbaza_roles.contractor') : trans('transbaza_roles.customer')
                            @endphp
                            <a href="{{route('profile_index')}}">{{$role_name}}</a>
                        </div>
                        {{--if user has multi roles--}}
                    @elseif(Auth::user()->checkRole(['performer', 'customer']) || (Auth::user()->checkRole(['admin']) || Auth::user()->isContentAdmin()))
                        <div class="form-item">
                            <div class="custom-select-exp">
                                <select name="change_role">
                                    <option value="">  @lang('transbaza_roles.choose_role')</option>
                                    @if(Auth::user()->checkRole(['customer']))
                                        <option value="customer" {{Auth::user()->isCustomer() ? 'selected' : ''}}>
                                            @lang('transbaza_roles.customer')
                                        </option> @endif
                                    @if(Auth::user()->checkRole(['performer']))
                                        <option value="contractor" {{Auth::user()->isContractor() ? 'selected' : ''}}>
                                            @lang('transbaza_roles.contractor')
                                        </option>
                                    @endif
                                    @if(Auth::user()->checkRole(['widget']))
                                        <option value="widget">
                                            @lang('transbaza_roles.widget')
                                        </option>
                                    @endif
                                    @if(Auth::user()->checkRole(['admin']) || Auth::user()->isContentAdmin())
                                        <option value="admin">
                                            @lang('transbaza_roles.admin')
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
                            <li><a href="{{route('profile_index')}}">@lang('transbaza_menu.profile')</a></li>
                           {{-- <li><a href="{{ route('chat_index') }}">@lang('transbaza_menu.chat')</a></li>--}}
                            <li><a href="/{{Auth::user()->getCurrentRoleName()}}/balance">@lang('transbaza_menu.my_finance')</a></li>
                            <li><a href="{{ route('my-services.index') }}">@lang('transbaza_menu.my_services')</a></li>
                            <li><a href="{{ route('friends.index') }}">@lang('transbaza_menu.my_friends')</a></li>
                            <li><a href="{{ route('adverts.index') }}">@lang('transbaza_menu.my_adverts')</a></li>
                            <li><a href="{{ route('subscribes.index') }}">@lang('transbaza_menu.my_subscribes')</a></li>
                            <li><a href="/{{Auth::user()->getCurrentRoleName()}}/documents">@lang('transbaza_menu.my_documents')</a></li>
                            <li><a href="{{route('support.index')}}">@lang('transbaza_menu.support')</a></li>
                            <li><a href="{{env('APP_URL')}}/stat">@lang('transbaza_menu.stat')</a></li>
                            <li><a href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">@lang('transbaza_menu.exit')</a></li>
                        </ul>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            @endauth

            {{--@admin
            @include('includes.languages')
            @endAdmin--}}
        </nav>

        <div class="mobile-menu">
            @auth
                <div class="user-role mobile-view">
                    <div class="form-item">
                        <div class="custom-select-exp">
                            <select name="change_role">
                                <option>Выберите роль</option>
                                @if(Auth::user()->checkRole('performer'))
                                    <option value="contractor" {{Auth::user()->isContractor() ? 'selected' : ''}}>
                                       @lang('transbaza_roles.contractor')
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('customer'))
                                    <option value="customer" {{Auth::user()->isCustomer() ? 'selected' : ''}}> @lang('transbaza_roles.customer')
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('widget'))
                                    <option value="widget"> @lang('transbaza_roles.widget')
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('admin'))
                                    <option value="admin"> @lang('transbaza_roles.admin')
                                    </option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            @endauth
            @guest
                <div class="auth-btn button">
                    <a href="{{route('login')}}" class="btn-custom">@lang('transbaza_login.enter')</a>
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
                    @guest
                        @foreach($global_static_contents as $content)
                            <li class="{{$content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty()  ? 'has-sub-m' : ''}}">
                                <a href="{{env('APP_URL')}}{{$content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty() ? '#' : "/{$content->alias}"}}">{{$content->menu_title}}</a>
                                @if($content->subMenuArticles->isNotEmpty() || $content->subMenuArticlesSections->isNotEmpty() )
                                    <ul class="ul-hide">
                                        <li>
                                            <a href="{{env('APP_URL')}}/{{$content->alias}}">{{$content->menu_title}}</a>
                                        </li>
                                        @foreach($content->subMenuArticlesSections as $sub_sec)
                                            <li><a href="{{env('APP_URL')}}/{{$sub_sec->alias}}">{{$sub_sec->name}}</a>
                                            </li>
                                        @endforeach
                                        @foreach($content->subMenuArticles as $sub)
                                            <li><a href="{{env('APP_URL')}}/{{$sub->alias}}">{{$sub->title}}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                       {{-- <li class="has-sub-m"><a href="{{env('APP_URL')}}/faq">FAQ</a>
                        </li>--}}
                        {{--<li><a href="/for-customer">Заказчику</a></li>
                        <li><a href="/for-contractor">Исполнителю</a></li>
                        <li><a href="/for-partner">Партнеру</a></li>--}}
                        <li><a href="/register">@lang('transbaza_register.title')</a></li>
                    @endguest

                    @auth
                        <li><a href="{{route('profile_index')}}">@lang('transbaza_menu.profile')</a></li>
                      {{--  <li><a href="{{ route('chat_index') }}">@lang('transbaza_menu.chat')</a></li>--}}
                        <li><a href="/{{Auth::user()->getCurrentRoleName()}}/balance">@lang('transbaza_menu.my_finance')</a></li>
                        <li><a href="{{ route('my-services.index') }}">@lang('transbaza_menu.my_services')</a></li>
                        <li><a href="{{ route('friends.index') }}">@lang('transbaza_menu.my_friends')</a></li>
                        <li><a href="{{ route('adverts.index') }}">@lang('transbaza_menu.my_adverts')</a></li>
                        <li><a href="{{ route('subscribes.index') }}">@lang('transbaza_menu.my_subscribes')</a></li>
                        <li><a href="/{{Auth::user()->getCurrentRoleName()}}/documents">@lang('transbaza_menu.my_documents')</a></li>
                        <li><a href="{{route('support.index')}}">@lang('transbaza_menu.support')</a></li>
                        <li><a href="{{env('APP_URL')}}/stat">@lang('transbaza_menu.stat')</a></li>
                        <li><a href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">@lang('transbaza_menu.exit')</a></li>
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
        @include('includes.languages')
    </div>
</header>
@auth

    {{--  @if(Request::path() !== '/' && !Request::filled('__webwidget'))--}}
    @include('user.sections.header-menu')
    {{--  @endif--}}
@endauth