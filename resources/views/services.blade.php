@extends('layouts.main')

@section('content')
    <div class="container">
        <div class="article-wrap">
            <ol class="breadcrumb">
                <li><a href="{{route('index')}}">@lang('transbaza_home.breadcrumb_home')</a></li>
                <li class="active">@lang('transbaza_services.title_h1')</li>
            </ol>
        </div>
        @if($services->count())
            <h1 class="title">@lang('transbaza_services.title_h1')</h1>
            <div class="news-list" style="margin-top: 40px;">
                @foreach($services as $value)
                    <div class="item">
                        @if($value->image)
                            <div class="image-wrap" style="max-height: 200px">
                                <a href="{{route('show_service', $value->alias)}}"><img src="/{{$value->image}}" alt="{{$value->title}}"></a>
                            </div>
                        @endif
                        <div class="content-wrap">
                            <a href="{{route('show_service', $value->alias)}}" class="title  no-decoration" >{{$value->title}}</a>
                            <p style="    margin-top: 10px;"><a href="{{route('show_service', $value->alias)}}" class="detail">@lang('transbaza_home.more')</a></p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

