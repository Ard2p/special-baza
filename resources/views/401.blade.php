@extends('layouts.main')
@section('content')
    <div class="search-wrap">
            <div class="not-found-wrap">
                <h3>Действие не доступно для данной роли</h3>
                <div class="button">
                    <a href="https://{{(env('APP_ROUTE_URL'))}}" class="btn-custom black" data-toggle="modal">На главную</a>
                </div>
            </div>
    </div>
@endsection