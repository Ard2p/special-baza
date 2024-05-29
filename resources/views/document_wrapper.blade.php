<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{$name}}</title>
    <style>
        @page {
            size: 21cm 29.7cm;
        }
        div, p, span {
            line-height: 1;
        }
    </style>
</head>
<body>
{!! \Illuminate\Support\Str::replace('<!-- pagebreak -->', '<div style="break-after:page"></div>', $html) !!}
</body>
</html>
