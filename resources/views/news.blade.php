@extends('layouts.main')

@section('content')
    <div class="container">
        <div class="article-wrap">
            <ol class="breadcrumb">
                <li><a href="{{route('index')}}">@lang('transbaza_home.breadcrumb_home')</a></li>
                <li class="active">@lang('transbaza_home.news')</li>
            </ol>
        </div>
        @if($articles->count())
            <h1 class="title">@lang('transbaza_home.news')</h1>
            <div class="news-list" style="margin-top: 40px;">

                @include('list')
            </div>
        @endif
    </div>
@endsection

