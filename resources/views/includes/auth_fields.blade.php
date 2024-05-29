<form action="{{route('login')}}" method="post" id="auth_form">
    @csrf
    <h2 class="title">@lang('transbaza_login.title')</h2>
    <div class="form-item image-item">
        <label for="">
            @lang('transbaza_login.your_email')
            <input type="text" name="email" placeholder="@lang('transbaza_menu.type_email')">
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
    <input type="hidden" name="redirect_back" value="{{request()->fullUrl()}}">
    <div class="button">
        <button type="submit" class="btn-custom">@lang('transbaza_menu.enter')</button>
    </div>
</form>
{{--<div class="button">
    <a href="{{route('facebook_redirect')}}" class="btn-custom black" style="position: relative;font-size: 12px;"><img style="width: 32px;position: absolute;left: 2px;" src="/images/social/fb.png"> @lang('transbaza_register.fb_login')</a>
    <a href="{{route('vkontakte_redirect')}}" class="btn-custom black" style="position: relative;font-size: 12px;margin-top: 10px;"><img style="width: 32px;position: absolute;left: 2px;" src="/images/social/vk.png"> @lang('transbaza_register.vk_login')</a>
</div>--}}
{{--<div>search-vehicles-data
    <a href="{{route('facebook_redirect')}}">
        <img style="width: 32px;" src="/images/social/fb.png"></a>
    </a>
    <a href="{{route('vkontakte_redirect')}}"><img style="width: 32px;" src="/images/social/vk.png"></a>
</div>--}}
{{--
<hr>
<a href="/password/reset" class="link-register"><span
            class="red">@lang('transbaza_menu.forget_password')</span></a>
<div class="button">
    <a href="/register" class="btn-custom black">@lang('transbaza_menu.register')</a>
</div>
--}}
