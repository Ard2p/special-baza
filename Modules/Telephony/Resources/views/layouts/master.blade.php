<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <title>Телефония</title>
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="/css/jquery.datetimepicker.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

<style>
    span.down {
        position: absolute;
        top: 50%;
        right: 15px;
        width: 20px;
        height: 20px;
        margin-top: -10px;
    }
    span.down:after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 11px;
        height: 7px;
        margin-top: -3.5px;
        margin-left: -5.5px;
        background: url(/images/arrow-down.png?d11f38b…) no-repeat 50%;
    }
    label{
        width: 100%;
    }
    .absolute-width {
        clear: both;
        position: fixed;
        width: 100%;
        top: 0;
        left: 0;
        height: 100%;
        z-index: 9;
        background: #00000052;
    }
    #phone_block{
        width: auto;
        height: auto;
        position: fixed;
        left: 6%;
        top: 20%;
        z-index: 999;
    }
    #mightyCallWebPhoneFrame{
        width: 365px !important;
        height: 502px !important;
    }
    #callme {
        position: fixed;
        right: 50px;
        bottom: 100px;
        width: 70px;
        height: 70px;
        cursor: pointer;
        opacity: 0.5;
        z-index: 99990;
    }
    #bottom_line{
        position: fixed;
        bottom: 0px;
        height: 20px;
        width: 100%;
        background: black;
    }
    #bottom_line div{
        color: white;
        font-weight: bold;
    }
    #callme #callmeMain {
        -moz-border-radius: 50% !important;
        -webkit-border-radius: 50% !important;
        border-radius: 50% !important;
        -moz-background-clip: padding;
        -webkit-background-clip: padding-box;
        background-clip: padding-box;
        background-color: rgb(67, 255, 46);
        width: 70px;
        height: 70px;
        -webkit-animation: zcwmini2 1.5s 0s ease-out infinite;
        -moz-animation: zcwmini2 1.5s 0s ease-out infinite;
        animation: zcwmini2 1.5s 0s ease-out infinite;
    }
    #callme #callmeMain:before {
        content: "";
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background-image: url(https://ss.zadarma.com/callbackWidget/images/mini.png);
        background-repeat: no-repeat;
        background-position: center center;
        -webkit-animation: zcwphone2 1.5s linear infinite;
        -moz-animation: zcwphone2 1.5s linear infinite;
        animation: zcwphone2 1.5s linear infinite;
    }
    @-webkit-keyframes zcwphone {
        0% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        25% {-ms-transform:rotate(30deg);-webkit-transform:rotate(30deg);transform:rotate(30deg);}
        50% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        75% {-ms-transform:rotate(-30deg);-webkit-transform:rotate(-30deg);transform:rotate(-30deg);}
        100% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
    }
    @-moz-keyframes zcwphone {
        0% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        25% {-ms-transform:rotate(30deg);-webkit-transform:rotate(30deg);transform:rotate(30deg);}
        50% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        75% {-ms-transform:rotate(-30deg);-webkit-transform:rotate(-30deg);transform:rotate(-30deg);}
        100% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
    }
    @keyframes zcwphone {
        0% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        25% {-ms-transform:rotate(30deg);-webkit-transform:rotate(30deg);transform:rotate(30deg);}
        50% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        75% {-ms-transform:rotate(-30deg);-webkit-transform:rotate(-30deg);transform:rotate(-30deg);}
        100% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
    }
    @-webkit-keyframes zcwphone2 {
        0% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        25% {-ms-transform:rotate(30deg);-webkit-transform:rotate(30deg);transform:rotate(30deg);}
        50% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        75% {-ms-transform:rotate(-30deg);-webkit-transform:rotate(-30deg);transform:rotate(-30deg);}
        100% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
    }
    @-moz-keyframes zcwphone2 {
        0% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        25% {-ms-transform:rotate(30deg);-webkit-transform:rotate(30deg);transform:rotate(30deg);}
        50% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        75% {-ms-transform:rotate(-30deg);-webkit-transform:rotate(-30deg);transform:rotate(-30deg);}
        100% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
    }
    @keyframes zcwphone2 {
        0% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        25% {-ms-transform:rotate(30deg);-webkit-transform:rotate(30deg);transform:rotate(30deg);}
        50% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
        75% {-ms-transform:rotate(-30deg);-webkit-transform:rotate(-30deg);transform:rotate(-30deg);}
        100% {-ms-transform:rotate(0deg);-webkit-transform:rotate(0deg);transform:rotate(0deg);}
    }
    @-webkit-keyframes zcwmini {
        0% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 0 rgba(0,0,0,0), 0 0 0 0 rgba(207,8,8, 0);}
        10% {box-shadow: 0 0 8px 6px , 0 0 12px 10px rgba(0,0,0,0), 0 0 12px 14px ;}
        100% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 40px rgba(0,0,0,0), 0 0 0 40px rgba(207,8,8, 0);}
    }
    @-moz-keyframes zcwmini {
        0% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 0 rgba(0,0,0,0), 0 0 0 0 rgba(207,8,8, 0);}
        10% {box-shadow: 0 0 8px 6px , 0 0 12px 10px rgba(0,0,0,0), 0 0 12px 14px ;}
        100% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 40px rgba(0,0,0,0), 0 0 0 40px rgba(207,8,8, 0);}
    }
    @keyframes zcwmini {
        0% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 0 rgba(0,0,0,0), 0 0 0 0 rgba(207,8,8, 0);}
        10% {box-shadow: 0 0 8px 6px , 0 0 12px 10px rgba(0,0,0,0), 0 0 12px 14px ;}
        100% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 40px rgba(0,0,0,0), 0 0 0 40px rgba(207,8,8, 0);}
    }
    @-webkit-keyframes zcwmini2 {
        0% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 0 rgba(0,0,0,0), 0 0 0 0 rgba(207,8,8, 0);}
        10% {box-shadow: 0 0 8px 6px , 0 0 12px 10px rgba(0,0,0,0), 0 0 12px 14px ;}
        100% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 40px rgba(0,0,0,0), 0 0 0 40px rgba(207,8,8, 0);}
    }
    @-moz-keyframes zcwmini2 {
        0% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 0 rgba(0,0,0,0), 0 0 0 0 rgba(207,8,8, 0);}
        10% {box-shadow: 0 0 8px 6px , 0 0 12px 10px rgba(0,0,0,0), 0 0 12px 14px ;}
        100% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 40px rgba(0,0,0,0), 0 0 0 40px rgba(207,8,8, 0);}
    }
    @keyframes zcwmini2 {
        0% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 0 rgba(0,0,0,0), 0 0 0 0 rgba(207,8,8, 0);}
        10% {box-shadow: 0 0 8px 6px , 0 0 12px 10px rgba(0,0,0,0), 0 0 12px 14px ;}
        100% {box-shadow: 0 0 8px 6px rgba(207,8,8, 0), 0 0 0 40px rgba(0,0,0,0), 0 0 0 40px rgba(207,8,8, 0);}
    }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark static-top">
    <div class="container">
        <a class="navbar-brand" href="#">TRANS-BAZA.RU ТЕЛЕФОНИЯ</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive"
                aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ml-auto">
               {{-- <li class="nav-item active">
                    <a class="nav-link" href="#">Home
                        <span class="sr-only">(current)</span>
                    </a>
                </li>--}}
            </ul>
        </div>
    </div>
</nav>
<div id="app" class="container-fluid col-md-10">
    <div class="row mt-2">
        @yield('content')
    </div>
</div>

<script src="https://api.yandex.mightycall.ru/api/v3/sdk/mightycall.webphone.sdk.js">

</script>
<script>
    navigator.mediaDevices.getUserMedia({
        audio: true
    }).then(function(stream) {

    });
</script>
{{-- Laravel Mix - JS File --}}
<script src="{{route('assets.lang')}}"></script>
<script src="{{ ('/js/app.js') }}"></script>
<script src="{{ ('/js/adminTelephony.js') }}"></script>

@stack('scripts')
</body>
</html>
