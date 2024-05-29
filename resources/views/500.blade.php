<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TRANS-BAZA.RU Ошибка сервера</title>
    <link href="/css/bootstrap/bootstrap.css" rel="stylesheet" id="bootstrap-css">
    <link href="/css/jquery.datetimepicker.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker.css"
          rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css?{{time()}}">
    <link rel="stylesheet" href="/css/bootstrap/bootstrap-select.css">
    <link rel="stylesheet" href="/css/tables/tables.css?{{time()}}">
    <link rel="stylesheet" href="/css/calendar.css">
    <link rel="stylesheet" href="/css/hamburger.min.css">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#ffc40d">
    <meta name="theme-color" content="#ffffff">

    <link rel="stylesheet" href="/css/theme/normalize.css">
    <link rel="stylesheet" href="/css/theme/slick.css">
    <link rel="stylesheet" href="/css/theme/slick-theme.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="/css/font-awesome.css">
    <link rel="stylesheet" href="/css/theme/fontawesome-stars.css">


    <script src="/js/jquery-3.3.1.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>

    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css"
          media="screen">

    <link rel="stylesheet" href="/css/theme/style.css?{{time()}}">
    <link rel="stylesheet" href="/css/theme/adaptive.css?{{time()}}">

    <script src="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.js"></script>
    <style>
        .select-items div {
            word-break: break-all;
        }
    </style>
</head>
<body>
<!------ Include the above in your HEAD tag ---------->
<header>
    <div class="header-wrap">
        <div class="logo">
            <a href="/">
                <img src="/img/logos/logo-tb-eng-g-200.png" class="full" alt="">
                <img src="/img/logos/small.png" class="small" alt="">
            </a>
        </div>
        <nav>
        </nav>
    </div>
</header>




<section class="content main-page" id="app">
    <div class="search-wrap">
        <div class="not-found-wrap">
            <h3>Технические работы. Скоро все заработает.</h3>
            <div class="button">
                <a href="{{URL::previous()}}" class="btn-custom black">Вернуться</a>
            </div>
        </div>
    </div>
</section>

<div class="modal" id="errorsModal">
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

<script src="/js/app.js?{{time()}}"></script>

</body>
</html>