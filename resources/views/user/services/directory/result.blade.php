@extends('layouts.main')
{{--@section('header')
    <meta name="description"
          content="{{mb_strtolower($category->name)}}, {{($city->region->name)}}, {{($city->name)}}, характеристики, фото, предложения по аренде">
    <meta name="keywords"
          content="TRANSBAZA {{mb_strtolower($category->name)}} {{mb_strtolower($city->region->name)}} {{mb_strtolower($city->name)}} характеристики фото аренда">
    <title>TRANSBAZA – {{mb_strtolower($category->name)}}, {{($city->region->name)}}, {{($city->name)}}</title>
@endsection--}}
@section('content')
    <div class="container article-wrap">
        <div class="col-md-9 col-md-push-3">
        <ol class="breadcrumb">
            <li> <a href="{{route('directory_main')}}">Спецтехника</a></li>
            <li> <a href="{{route('directory_main_category', $category->alias)}}">Аренда {{$category->name_style}}</a></li>
            <li class="active">В городе {{$city->name}}, {{$city->region->name}}</li>
        </ol>
        <h1 style="background: yellow">{{$category->name_style}} в городе {{$city->name}}
            , {{$city->region->name}}</h1>

        <h4>По данным TRANSBAZA, в этом городе услуги оказывает:</h4>
        <div class="alert alert-success" id="success" style="display: none">
            Ваша заявка успешно отправлена!
        </div>

        <table class="table table-striped">
            <thead>
            <th>#</th>
            <th>Телефон</th>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>#{{$user->id}}</td>
                    <td>
                        @if($user->contractor_alias_enable)
                            <a href="{{route('contractor_public_page', $user->contractor_alias)}}"
                               class="link-register">   {{$user->phone_format}}</a>

                        @else
                            {{$user->phone_format}}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <b>Телефон показывается только для зарегистрированных пользователей специального типа </b>
        <div class="col-md-12">
            @if(!Auth::check())
                <div class="col-md-6">
                    <div class="btn-col">
                        <div class="button">
                            <button onclick="location.href = '{{route('register')}}'" class="btn-custom">
                                Зарегистрироваться
                                в системе
                            </button>
                            <hr>
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-md-6">
                <div class="btn-col">
                    <div class="button">
                        <button class="btn-custom" data-toggle="modal"
                                data-target="#new_customer"> Я хочу добавить свой телефон в справочник
                        </button>
                        <hr>
                    </div>
                </div>
            </div>

        </div>

        <div class="clearfix"></div>
        <div class="machine-card">
            @seoTop
            @contactTop
            @foreach($services as $service)


                <div class="row">
                    <div>
                        <a href="{!! $service->rent_url !!}">
                            <h2 style="    margin: 15px;">{{$service->category->name}}  <p
                                        style="font-size: 15px;">{{$service->city->name ?? ''}}
                                    , {{$service->region->name ?? ''}}</p></h2>
                        </a>
                    </div>

                    <div class="col-md-6  proposal-wrap ">
                        <div class="list-data">
                            <p>
                                                    <span><b>{{$service->name}}</b></span>
                            </p>


                        </div>
                        <div class="image-wrap">
                            <a class="thumbnail fancybox" rel="ligthbox"
                               href="{!! $service->rent_url !!}">
                                <img alt="{{$service->category->name}}  {{$service->city->name ?? ''}}, {{$service->region->name ?? ''}}"
                                     src="/{{$service->photo}}" class="img-responsive"
                                     style="max-height: 400px;"></a>
                            <input id="profile-image-upload" class="hidden" type="file">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="list-params">
                            @include('user.services.directory.list_attributes')

                        </div>
                        <div class="form-item">
                            <div class="button">
                                <a class="btn-custom"
                                   href="{!! $service->rent_url !!}">Заказать
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center">
            {{$services->links( "pagination::bootstrap-4")}}
        </div>
        @seoBottom
            @contactBottom
        </div>
        <div class="col-md-3 col-md-pull-9">
            @include('includes.auth_form')
        </div>
        <div class="modal modal-fade" id="proposal-modal" style="display: none;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span
                                    aria-hidden="true">×</span><span
                                    class="sr-only">Close</span></button>
                        <h4 class="modal-title">
                            Быстрый заказ
                        </h4>
                    </div>
                    <form class="rent-form" action="{{route('make_proposal')}}">
                        <div class="modal-body order-modal"
                             style="max-height: calc(100vh - 200px);overflow-y: auto;">
                            <div class="col-md-6 col-sm-6 col-xs-6">
                                <div class="form-item">
                                    <label for="radio-input-yes" class="radio">
                                        Я новый заказчик
                                        <input type="radio" class="__radio"
                                               name="customer_type" value="new"
                                               id="radio-input-yes" checked>
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-6">
                                <div class="form-item">
                                    <label for="radio-input-no" class="radio">
                                        Я участник системы
                                        <input type="radio" class="__radio"
                                               name="customer_type" value="old"
                                               id="radio-input-no">
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="name" placeholder="Имя">
                                </label>
                            </div>
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="email" placeholder="Email">
                                </label>
                            </div>
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="phone" class="phone" placeholder="Телефон">
                                </label>
                            </div>
                            <input type="hidden" name="region" value="{{$region->id}}">
                            <input type="hidden" name="city_id" value="{{$city->id}}">
                            <input type="hidden" name="type_id" value="{{$category->id}}">
                            <div class="form-item">
                                <label>Тип техники:</label>
                                <input class="promo_code" value="{{$category->name}}"
                                       type="text" disabled>
                            </div>
                            <div class="form-item">
                                <label>Регион:</label>
                                <input class="promo_code" value="{{$region->name}}"
                                       type="text" disabled>
                            </div>
                            <div class="form-item">
                                <label>Город:</label>
                                <input class="promo_code" value="{{$city->name}}"
                                       type="text" disabled>
                            </div>
                            <div class="form-item small">
                                <label for="price">Адрес
                                    <input type="text" name="address"
                                           placeholder="Куда должна прибыть техника?">
                                </label>
                            </div>
                            @include('widget.bottom_form_piece')

                        </div>

                        <div class="modal-footer">
                            <div class="form-item">
                                <div class="button">
                                    <button type="submit" class="btn-custom">ОТПРАВИТЬ
                                        ЗАЯВКУ
                                    </button>
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="button">
                                    <button type="button" data-dismiss="modal" class="btn-custom black">
                                        Отмена
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal modal-fade" id="new_customer" style="display: none;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span
                                    aria-hidden="true">×</span><span
                                    class="sr-only">Close</span></button>
                        <h4 class="modal-title">
                            Бесплатно подать заявку на внесение информации обо мне в справочник
                        </h4>
                    </div>
                    <form id="request_contractor" action="{{route('new_contractor_request')}}">
                        @csrf
                        <div class="modal-body order-modal"
                             style="max-height: calc(100vh - 200px);overflow-y: auto;">
                            <div class="tree-cols-list">
                                <div class="col">
                                    <helper-select-input :data="{{\App\Machines\Type::all()->toJson()}}"
                                                         :column-name="{{json_encode('Категория техники')}}"
                                                         :place-holder="{{json_encode('Выберите категорию')}}"
                                                         :col-name="{{json_encode('type_id')}}"
                                                         :initial="{{json_encode($category->toArray() ?? '')}}"
                                                         :show-column-name="1"></helper-select-input>
                                </div>
                                <div class="col">
                                    <helper-select-input :data="{{\App\Support\Region::all()->toJson()}}"
                                                         :column-name="{{json_encode('Регион')}}"
                                                         :place-holder="{{json_encode('Выберите регион')}}"
                                                         :col-name="{{json_encode('region')}}"
                                                         :required="0"
                                                         :initial="{{json_encode($city->region->toArray() ?? '')}}"
                                                         :initial-city="{{json_encode($city->toArray() ?? '')}}"
                                                         :city-data="{{json_encode([])}}"
                                                         :show-column-name="1"
                                                         :hide-city="1">
                                    </helper-select-input>

                                </div>
                                <div class="col">
                                    <helper-select-input :data="{{json_encode([])}}"
                                                         :column-name="{{json_encode('Город')}}"
                                                         :place-holder="{{json_encode('Город')}}"
                                                         :required="0"
                                                         :col-name="{{json_encode('city_id')}}"
                                                         :initial="{{json_encode($city->toArray() ?? '')}}"
                                                         :show-column-name="1"
                                                         :hide-city="1"></helper-select-input>

                                </div>

                            </div>

                            <div class="form-item small">
                                <label>
                                    <input type="text" name="name" placeholder="Имя">
                                </label>
                            </div>
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="email" placeholder="Email">
                                </label>
                            </div>
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="phone" class="phone" placeholder="Телефон">
                                </label>
                            </div>
                            <div class="form-item small">
                                <label>
                                    <input type="text" name="comment" placeholder="Примечание">
                                </label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <div class="form-item">
                                <div class="button">
                                    <button type="submit" class="btn-custom">ОТПРАВИТЬ
                                        ЗАЯВКУ
                                    </button>
                                </div>
                            </div>
                            <div class="form-item">
                                <div class="button">
                                    <button type="button" data-dismiss="modal" class="btn-custom black">
                                        Отмена
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection