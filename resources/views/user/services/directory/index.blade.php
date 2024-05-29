@extends('layouts.main')
@section('header')
    <meta name="description" content="Каталог услуг системы TRANSBAZA">
    <meta name="keywords" content="TRANSBAZA справочник услуг аренда цена">
    <title>Справочник TRANSBAZA –  по оказанию услуг</title>
@endsection
@section('content')
    <div class="container article-wrap">
        <div class="col-md-9 col-md-push-3">
            <ol class="breadcrumb">
                <li class="active">Услуги</li>
            </ol>
            <h1>Аренда техники TRANSBAZA</h1>

            <div class="clearfix"></div>
            @seoTop
            @contactTop
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <th>Категория спецтехники</th>
                    </thead>
                    <tbody>
                    @foreach($cats as $cat)
                        <tr>
                            <td><a href="{{route('contractor_service_directory_main_category', $cat->alias)}}">{{$cat->name}}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @seoBottom
            @contactBottom
            {!! \App\Marketing\ShareList::renderShare() !!}
        </div>
            <div class="col-md-3 col-md-pull-9">
                @include('includes.auth_form')
            </div>
    </div>
@endsection