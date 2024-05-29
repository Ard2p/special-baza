@extends('layouts.main')
@section('header')
    <meta name="description" content="Страница владельца #{{$user->id}} на платформе TRANSBAZA">
    <meta name="keywords" content="@lang('public_profile.meta_keywords')">
    <title>TRANSBAZA - Владелец техники #{{$user->id}}, быстрый заказ спецтехники</title>
@endsection
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">


            <div class="col-md-12">
                <div id="machine_show">

                    <div class="machine-card">
                        <div class="row">
                            <div class="col-md-12">
                                <h1>@lang('public_profile.user') # {{$user->id}}</h1>
                            </div>
                            <div class="col-md-4">
                                <div class="image-wrap">
                                    <a class="thumbnail fancybox" rel="ligthbox" href=" {{$user->avatar}}">
                                        <img alt="Фото техники"
                                             src="{{$user->avatar}}" class="img-responsive"></a>
                                    <input id="profile-image-upload" class="hidden" type="file">
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="list-params">
                                    <p>
                                        <strong>@lang('public_profile.register_date')</strong>
                                        {{$user->created_at->format('d.m.Y')}}
                                    </p>

                              {{--      <p>
                                        <strong>Кол-во друзей:</strong>
                                        {{$user->my_friends->count()}}
                                    </p>

                                    <p>
                                        <strong>Кол-во взаимных друзей:</strong>
                                        {{$user->my_submitted_friends->count()}}
                                    </p>--}}

                                    <p>
                                        <strong>@lang('public_profile.vehicles_count')</strong>
                                        <a href="{{$user->machines->count() > 0 ? route('contractor_public_page', $user->contractor_alias) : '#'}}">{{$user->machines->count()}}</a>
                                    </p>

                                 {{--   <p>
                                        <strong>Кол-во услуг:</strong>
                                        <a href="{{$user->services->count() > 0 ? route('contractor_service_public_page', $user->contractor_alias) : '#'}}">{{$user->services->count()}}</a>
                                    </p>--}}
                                    <p>
                                        <strong>@lang('public_profile.adverts_count')</strong>
                                        <a href="{{route('adverts_public_page', $user->contractor_alias)}}">{{$user->adverts->count()}}</a>
                                    </p>
                                    <p>
                                        <strong>@lang('public_profile.rate')</strong>
                                        <a href="#">0</a>
                                    </p>

                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="col-md-12 harmony-accord"><h3 >@lang('public_profile.feedbacks')</h3>
                                    <div class="list-params">
                                        <div class="machinery-filter-wrap">
                                            @if($user->getFeedbacks()->count())
                                                <div class="comments-wrap">
                                                @foreach($user->getFeedbacks() as $feedback)
                                                    <div class="comment">
                                                        <div class="logo-user">
                                                            <img src="{{$feedback->user->avatar}}" alt="">
                                                        </div>
                                                        <div class="detail-comment">
                                                            <div class="author-data">
                                                                <p class="full-name">Отзыв исполнителя:</p>
                                                                <div class="raiting">
                                                                    <select id="performer_rate" data-rate="{{$feedback->rate}}">
                                                                        <option value="1">1</option>
                                                                        <option value="2">2</option>
                                                                        <option value="3">3</option>
                                                                        <option value="4">4</option>
                                                                        <option value="5">5</option>
                                                                    </select>
                                                                </div>
                                                                <p class="date">{{$feedback->updated_at->format('d.m.Y H:i')}}</p>
                                                            </div>
                                                            <div class="comment-data">
                                                                <p>
                                                                    {{$feedback->feedback}}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                </div>
                                            @else
                                                 <h4>@lang('public_profile.no_feedbacks')</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="button two-btn">
                                    <a href="{{ route('contractor_public_page', $user->contractor_alias)}}"
                                       class="btn-custom black">@lang('public_profile.rent_vehicles')</a> <span></span>{{-- <a
                                            href="{{route('contractor_service_public_page', $user->contractor_alias)}}"
                                            class="btn-custom black"> Заказать услуги</a>--}}</div>
                            </div>
                            <div class="clearfix"></div>

                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="margin-wrap"></div>
                </div>
                {!! \App\Marketing\ShareList::renderShare() !!}
            </div>

        </div>
    </div>
    @push('after-scripts')
        <script>
            $(document).on('click', '.roller-item .item', function () {
                $(this).siblings('.content').toggleClass('active')
                $(this).find('.fas').toggleClass('active')
            })
        </script>
    @endpush
@endsection