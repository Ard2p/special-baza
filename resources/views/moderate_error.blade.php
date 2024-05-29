@extends('layouts.main')
@section('content')
    <div class="search-wrap">
            <div class="not-found-wrap">
                <h3>Контент находится на модерации.</h3>
                <div class="button">
                    <a href="{{URL::previous()}}" class="btn-custom black">Вернуться</a>
                </div>
            </div>
    </div>
@endsection