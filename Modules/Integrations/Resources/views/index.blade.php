@extends('layouts.main')

@section('content')

    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>@lang('transbaza_widgets.settings_title')</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>

                <div class="col-md-offset-1 col-md-8 search-wrap user-profile-wrap box-shadow-wrap">

                    <h3 class="title">Настройка API</h3>
                    <ol class="breadcrumb">
                        <li><a href="/">Главная</a></li>
                        <li class="active">Редактировать API</li>
                    </ol>
                    <form method="POST" action="{{route('update_api')}}">

                        @csrf
                        <div class="detail-search ">

                                <div class="col">
                                    <div class="form-item"><label>Ваш URL для уведомлений</label>
                                        <input name="url" value="{{$api->event_back_url}}"
                                               type="text"></div>
                                </div>

                        </div>
                        <div class="btn-col">
                            <div class="button">
                                <button type="submit"
                                        class="btn-custom">@lang('transbaza_widgets.settings_save')
                                </button>
                            </div>
                        </div>
                    </form>
                    <hr>
                </div>
            </div>
        </div>
    </div>

@stop