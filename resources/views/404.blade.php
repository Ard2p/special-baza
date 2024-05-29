@extends('layouts.main')
@section('content')
    <div class="search-wrap">
            <div class="not-found-wrap">
                <h3>Запрашиваемая страница не найдена.</h3>
                <div class="button">
                    <a href="{{env('APP_URL')}}" class="btn-custom black">На главную</a>
                </div>
            </div>
    </div>
@endsection