<div id="popup__toggle" style="display: none">
    @php
        $q = http_build_query(['phone' => '79256070803', 'text' => 'У меня вопрос:']);
    $whatsapp = "https://api.whatsapp.com/send?{$q}";
    @endphp
    <a href="{{$whatsapp}}" class="show-pop-btn">
        <div class="circle-chat show-pop-btn">

        </div>
    </a>

    <a href="{{$whatsapp}}" class="show-pop-btn">
        <div class="circle-phone ">

        </div>
    </a>
    <a href="#" id="__show_form" class="show-pop-btn">
        <div class="circle-form">

        </div>
    </a>
    <div class="circlephone" style="transform-origin: center;"></div>
    <div class="circle-fill" style="transform-origin: center;"></div>
    <div class="img-circle" style="transform-origin: center;">
        <div class="img-circleblock" style="transform-origin: center;"></div>
    </div>
</div>
<div class="clearfix"></div>