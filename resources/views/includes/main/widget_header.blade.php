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
                                        <option value="customer">
                                            @lang('transbaza_roles.customer')
                                        </option> @endif
                                    @if(Auth::user()->checkRole(['performer']))
                                        <option value="contractor">
                                            @lang('transbaza_roles.contractor')
                                        </option>
                                    @endif
                                    @if(Auth::user()->checkRole(['widget']))
                                        <option value="widget" selected>   @lang('transbaza_roles.widget')
                                        </option>
                                    @endif
                                    @if(Auth::user()->checkRole(['admin']) || Auth::user()->isContentAdmin())
                                        <option value="admin">   @lang('transbaza_roles.admin')
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
                            {{--   <li><a href="/{{Auth::user()->getCurrentRoleName()}}/requisites">Реквизиты</a></li>--}}
                            {{--   <li><a href="/{{Auth::user()->getCurrentRoleName()}}/payments">Платежи</a></li>--}}
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
                                        Исполнитель
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('customer'))
                                    <option value="customer">Заказчик
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('widget'))
                                    <option value="widget" selected>Виджет
                                    </option>
                                @endif
                                @if(Auth::user()->checkRole('admin'))
                                    <option value="admin">Администратор
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