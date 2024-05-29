@extends('layouts.main')
@section('header')
    <title>TRANSBAZA - {{$advert->name}}</title>
@endsection
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <div class=" @guest col-md-9 col-md-push-3 @endguest col-sm-12  user-profile-wrap box-shadow-wrap">

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="errors" style="display: none">
                            <div class="alert alert-warning" id="alerts" role="alert">


                            </div>
                        </div>
                    </div>
                    <div class="machine-card">
                        <div class="col-md-7">
                            <h3>{{$advert->name}}</h3>
                            <div class="list-params">
                                <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.show_sum') </strong><span>{{$advert->sum_format}}</span>
                                </p>
                                <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.show_category') </strong><span
                                            style="">{{$advert->category->name}}</span>
                                </p>
                                <p style="flex-wrap: wrap;">
                                    <strong style="margin-bottom: auto">@lang('transbaza_adverts.show_description') </strong><span>{!! nl2br(e($advert->description)) !!}</span>
                                </p>

                                <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.show_agent_bonus') </strong>
                                    <span>{{$advert->reward->name}} {{$advert->reward_text}}</span>
                                </p>
                                @if(Auth::check() && Auth::user()->id !== $advert->user_id)
                                    @if(isset($link) || $advert->isAgent())
                                        <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.distance to_author') </strong>
                                            <span>{{$advert->getAgentsCount()}} @lang('transbaza_adverts.agents')</span>
                                        </p>
                                    @endif
                                @else
                                    @isset($link)
                                        <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.distance to_author') </strong>
                                            <span>{{$advert->getAgentsCount()}} @lang('transbaza_adverts.agents') </span>
                                        </p>
                                    @endisset
                                @endif
                                <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.show_actual_date') </strong>
                                    <span> {{$advert->actual_date->format('d.m.Y')}}</span>
                                </p>

                                <p style="flex-wrap: wrap;"><strong> @lang('transbaza_adverts.show_address') </strong>
                                    <span id="addressData">{{$advert->full_address}}</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-5 text-center">

                            <div class="row clearfix body" style="    max-height: 300px;">
                                <img src="{{url($advert->photo)}}" style="   max-height: 340px; width: 100%">
                            </div>
                            <hr>
                            <div>
                                <i class="fa fa-eye"></i> @lang('transbaza_adverts.shows') {{$advert->sum_views}}
                            </div>
                        </div>

                    </div>
                    @if($advert->isComplete() && Auth::check())
                        <div class="col-sm-12 col-md-12">
                            <div class="col-md-8">

                                @if($advert->isCustomer())
                                    <h4>@lang('transbaza_adverts.contacts')</h4>
                                    <p><strong>@lang('transbaza_adverts.contact_contractor') </strong> #{{$advert->winner->user->id ?? ''}}</p>
                                    <p><strong>@lang('transbaza_adverts.contact_phone_contractor') </strong> <span
                                                class="phone">{{$advert->winner->user->phone  ?? ''}}</span></p>
                                @endif
                                @if($advert->isContractor() && !$advert->hasFeedback())
                                    <h4>@lang('transbaza_adverts.contacts') </h4>
                                    <p><strong>@lang('transbaza_adverts.customer') </strong> #{{$advert->user->id ?? ''}}</p>
                                    <p><strong>@lang('transbaza_adverts.contact_phone_contractor') </strong> <span
                                                class="phone">{{$advert->user->phone  ?? ''}}</span></p>
                                    <form id="advert_feedback" method="post"
                                          action="{{ route('add_feedback_advert', $advert->alias) }}" class="form-item">
                                        @csrf
                                        <textarea type="text" class="form-control" name="feedback"></textarea>
                                        <input type="hidden" id="rate" name="rate" value="">
                                        <div class="row" style="margin:10px;">
                                            <div class="button">
                                                <button class="btn-custom" type="submit">@lang('transbaza_adverts.send_feedback') </button>
                                            </div>

                                            <div class="pull-right">
                                            <span class="stars">
                                                <i class="glyphicon glyphicon-star star cl" data-star="5"></i>
                                                <i class="glyphicon glyphicon-star star cl" data-star="4"></i>
                                                <i class="glyphicon glyphicon-star star cl" data-star="3"></i>
                                                <i class="glyphicon glyphicon-star star cl" data-star="2"></i>
                                                <i class="glyphicon glyphicon-star star cl" data-star="1"></i>
                                            </span>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                                @if(($advert->isCustomer() || $advert->isContractor()) && $advert->hasFeedback())
                                    <div class="comments-wrap ">
                                        <div class="comment">
                                            <div class="logo-user">
                                                <img src="{{$advert->winner->user->avatar}}" alt="">
                                            </div>
                                            <div class="detail-comment">
                                                <div class="author-data">
                                                    <p class="full-name">@lang('transbaza_adverts.contractor_feedback') </p>
                                                    <div class="raiting">
                                                        <select id="performer_rate"
                                                                data-rate="{{$advert->winner->rate}}">
                                                            <option value="1">1</option>
                                                            <option value="2">2</option>
                                                            <option value="3">3</option>
                                                            <option value="4">4</option>
                                                            <option value="5">5</option>
                                                        </select>
                                                    </div>
                                                    <p class="date">{{$advert->winner->updated_at->format('d.m.Y H:i')}}</p>
                                                </div>
                                                <div class="comment-data">
                                                    <p>
                                                        {{$advert->winner->feedback}}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    @endif

                    @include('user.adverts.agents_line')

                    <div class="machine-card col-md-12">
                        @if(Auth::check() && Auth::id() === $advert->user_id)
                            <div class="col-md-12 harmony-accord"><h3>@lang('transbaza_adverts.stats')</h3>
                                <div class="list-params">
                                    <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.stats_send_mails')
                                        </strong>{{$advert->sendingSms->count() + $advert->sendingEmails->count()}}
                                    </p>
                                    <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.stats_open_mails')
                                        </strong>{{$advert->open_mails}}
                                    </p>
                                    <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.stats_link_referrer')
                                        </strong>{{$advert->accept_mails}}
                                    </p>

                                    <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.stats_agents')
                                        </strong>{{$advert->agents->count()}}
                                    </p>
                                    <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.stats_accept_count')
                                        </strong>{{$advert->offers->count()}}
                                    </p>
                                    <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.stats_users_show')
                                        </strong>{{$advert->views}}
                                    </p>
                                    <p style="flex-wrap: wrap;"><strong>@lang('transbaza_adverts.stats_show_guest')
                                        </strong>{{$advert->guest_views}}
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <h4>@lang('transbaza_adverts.stats_agents_count') </h4>
                                <advert_tree
                                        tree-data="{{Auth::user()->getAdvertChildren($advert->id)->toJson()}}"></advert_tree>
                            </div>
                            <div class="col-md-12">
                                <h4>@lang('transbaza_adverts.offers') </h4>
                                @if($advert->offers->count())
                                    <table class="table table-striped table-bordered"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>@lang('transbaza_adverts.offers_table_sum') </th>
                                            <th>@lang('transbaza_adverts.offers_table_comment')

                                            <th class="text-center"><i class="fa fa-cog"></i></th>

                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($advert->offers as $offer)
                                            <tr>
                                                <td class="text-center">{{$offer->sum}}</td>
                                                <td>{{$offer->comment}}</td>

                                                <td class="text-center">
                                                    @if($advert->isActive())
                                                        <div class="button">
                                                            <form method="post"
                                                                  action="{{route('submit_advert_offer', $advert->alias)}}"
                                                                  class="accept_advert_offer">
                                                                @method('PATCH')
                                                                <input type="hidden" name="offer_id"
                                                                       value="{{$offer->id}}">
                                                                <button class="btn-custom">Принять</button>
                                                            </form>
                                                        </div>
                                                    @else
                                                        {{$offer->is_win ? 'Выбранный исполнитель' : ' Нет доступных действий'}}

                                                    @endif
                                                </td>

                                            </tr>
                                        @endforeach

                                        <tbody>
                                    </table>
                                @else
                                    @lang('transbaza_adverts.no_offers')
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($advert->isActive())
                        @if(Auth::check())
                            @if($advert->isAgent() && $advert->isActive())
                                <div class="col-md-12">

                                    <div class="col-md-12">
                                        <div id="tabs-panel"{{-- class="button search-btns"--}}>
                                            <ul class="nav nav-tabs" id="myTab">
                                                <li style="width: 33%"><a href="#email_list"
                                                                          class="{{--btn-custom black--}} active show"
                                                                          data-toggle="tab">@lang('transbaza_adverts.email_friends')</a></li>

                                                <li style="width: 33%"><a href="#sms_list" {{--class="btn-custom"--}}
                                                    data-toggle="tab">@lang('transbaza_adverts.phone_friends')</a></li>
                                            </ul>

                                            <div class="tab-content">

                                                <div class="tab-pane active" id="email_list">
                                                    <p class="h4">@lang('transbaza_adverts.email_friends')</p>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="btn-col">
                                                                <div class="button">
                                                                    <button id="send_mails" style="background: #ee2b24"
                                                                            data-url="{!! route('adverts', $advert->alias) !!}"
                                                                            class="btn-custom">@lang('transbaza_adverts.send_friends_email')
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-bordered"
                                                               style="width:100%" id="friends_table">
                                                            <thead>
                                                            <tr>
                                                                <th class="text-center"><input type="checkbox"
                                                                                               class="selectAll"></th>
                                                                <th>@lang('transbaza_adverts.table_show_email')</th>
                                                                <th>@lang('transbaza_adverts.table_show_notes')</th>
                                                                <th class="text-center"><i class="fa fa-cog"></i></th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>

                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="tab-pane" id="sms_list">
                                                    <div class="table-responsive">
                                                        <p class="h4">@lang('transbaza_adverts.phone_friends')</p>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="btn-col">
                                                                    <div class="button">
                                                                        <button id="send_sms"
                                                                                style="background: #ee2b24"
                                                                                data-url="{!! route('adverts', $advert->alias) !!}"
                                                                                class="btn-custom">@lang('transbaza_adverts.send_friends_sms')
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered"
                                                                   style="width:100%" id="friends_sms_table">
                                                                <thead>
                                                                <tr>
                                                                    <th class="text-center"><input type="checkbox"
                                                                                                   class="selectAllSms">
                                                                    </th>
                                                                    <th>@lang('transbaza_adverts.table_show_email')</th>
                                                                    <th>@lang('transbaza_adverts.table_show_notes')</th>
                                                                    <th class="text-center"><i class="fa fa-cog"></i>
                                                                    </th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>

                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            @elseif(!($current_offer = $advert->hasOffer(Auth::id())))
                                @include('user.adverts.buttons')
                            @else
                                <div class="proposal-wrap">
                                    <div class="col-md-7">
                                        <h4>@lang('transbaza_adverts.your_offer')</h4>
                                        <p><strong>@lang('transbaza_adverts.your_offer_sum') </strong>{{$current_offer->sum_format}}
                                        </p>
                                        <p><strong>@lang('transbaza_adverts.your_offer_comment') </strong>{{$current_offer->comment}}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @else
                            @include('user.adverts.buttons')
                        @endif
                    @endif
                </div>
                {!! \App\Marketing\ShareList::renderShare() !!}

            </div>

            @guest
                <div class="col-md-3    col-md-pull-9">
                    <section class="post-header">
                        <div class="auth" style="width: 100%;">
                            <form action="post" id="auth_form">
                                @csrf
                                <h2 class="title">Вход</h2>
                                <div class="form-item image-item">
                                    <label for="">
                                        Ваш email
                                        <input type="email" name="email" placeholder="Введите почту">
                                        <span class="image email"></span>
                                    </label>
                                    <span class="error"></span>
                                </div>
                                <div class="form-item image-item">
                                    <label for="">
                                        Ваш пароль
                                        <input type="password" name="password" placeholder="Введите пароль">
                                        <span class="image lock"></span>
                                    </label>
                                </div>
                                <div class="button">
                                    <button type="submit" class="btn-custom">войти</button>
                                </div>
                            </form>
                            <hr>
                            <a href="/password/reset" class="link-register"><span class="red">Забыли пароль?</span></a>
                            <div class="button">
                                <a href="/register" class="btn-custom black">Регистрация</a>
                            </div>
                        </div>

                    </section>
                    @include('includes.youtube')
                </div>
            @endguest
        </div>
    </div>
    <div class="modal" id="show_info" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Информация</h4>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer button two-btn">
                    <button type="button" class="btn-custom" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>

    @push('after-scripts')
        <script>
            var __advert_page = 1;
            var __advert_info_link = '{!! $advert->getInfoLink() !!}';
            var __set_me_contractor = '{!! $advert->setMeContractor() !!}';
            var __set_me_agent = '{!! $advert->setMeAgent() !!}';
        </script>
        @if(Auth::check())
            @if($advert->isAgent())
                <link rel="stylesheet" href="https://cdn.datatables.net/select/1.2.7/css/select.dataTables.min.css">
                <script src="/js/tables/dataTables.js"></script>
                <script src="/js/tables/tableBs.js"></script>
                <script src="https://cdn.datatables.net/select/1.2.7/js/dataTables.select.min.js"></script>
                <script>
                    var friends_table = $('#friends_table').DataTable({
                        "ajax": '{!!  route('friends.index', ['email_list' => 1, 'get_advert' => $advert->id])!!}',
                        "autoWidth": false,
                        "ordering": false,
                        "searching": false,
                        columnDefs: [{
                            orderable: false,
                            className: 'select-checkbox',
                            targets: 0
                        }],
                        select: {
                            style: 'multi',
                            selector: 'td:first-child'
                        },
                        "columns": [
                            {
                                sDefaultContent: "",
                                "render": function (data, type, full, meta) {
                                    if (type === 'display') {
                                        //data = '<input type="checkbox" class="friend_check" value="' + full['id'] + '">';

                                    }
                                    return data;
                                },
                            },
                            {
                                "data": "email",

                            },
                            {
                                "data": "name",
                            },
                            {
                                sDefaultContent: "",
                                "render": function (data, type, full, meta) {
                                    if (type === 'display') {
                                        data = '<a href="#" data-toggle="modal" data-type="email" data-link="' + full['info_link'] + '?get_advert={{$advert->id}}" data-target="#show_info" class="link_info">' + full['email_adverts_count'] + '</a>';

                                    }
                                    return data;
                                },
                            },
                        ],
                    })
                    var friends_sms_table = $('#friends_sms_table').DataTable({
                        "ajax": '{{route('friends.index', ['sms_list' => 1, 'get_advert' => $advert->id])}}',
                        "autoWidth": false,
                        "ordering": false,
                        "searching": false,
                        columnDefs: [{
                            orderable: false,
                            className: 'select-checkbox',
                            targets: 0
                        }],
                        select: {
                            style: 'multi',
                            selector: 'td:first-child'
                        },
                        "columns": [
                            {
                                sDefaultContent: "",
                                "render": function (data, type, full, meta) {
                                    if (type === 'display') {
                                        //data = '<input type="checkbox" class="friend_check" value="' + full['id'] + '">';

                                    }
                                    return data;
                                },
                            },
                            {
                                "data": "phone",
                            },
                            {
                                "data": "name",
                            },
                            {
                                sDefaultContent: "",
                                "render": function (data, type, full, meta) {
                                    if (type === 'display') {
                                        data = '<a href="#" data-toggle="modal" data-type="phone" data-link="' + full['info_link'] + '?get_advert={{$advert->id}}" data-target="#show_info" class="link_info">' + full['sms_adverts_count'] + '</a>';

                                    }
                                    return data;
                                },
                            },

                        ],
                    })

                    function getSelected(type) {
                        var selected
                        selected = type === 'email' ? friends_table.rows({selected: true}).data() : friends_sms_table.rows({selected: true}).data();

                        var arr = [], row;
                        selected = Object.values(selected)
                        for (row in selected) {

                            if (selected[row]['id'] !== undefined) {
                                arr.push(selected[row]['id'])
                            }
                        }
                        return arr
                    }

                    $(".selectAll").on("click", function (e) {
                        if ($(this).is(":checked")) {
                            friends_table.rows().select();
                        } else {
                            friends_table.rows().deselect();
                        }
                    });
                    $(".selectAllSms").on("click", function (e) {
                        if ($(this).is(":checked")) {
                            friends_sms_table.rows().select();
                        } else {
                            friends_sms_table.rows().deselect();
                        }
                    });
                    $(document).on('click', '#send_mails', function () {
                        let $btn = $(this);
                        $btn.prop('disabled', true);
                        let selected = getSelected('email');
                        if (!selected.length) {
                            swal.fire('Ничего не выбрано');
                            $btn.prop('disabled', false);
                            return;
                        }
                        $.ajax({
                            url: $btn.data('url'),
                            data: {
                                friends: selected,
                                type: 'email',
                            },
                            type: 'POST',
                            success: function (e) {
                                showMessage(e.message);
                                $btn.remove();
                            },
                            error: function (e) {
                                showErrors(e)
                                $btn.prop('disabled', false);
                            }
                        })
                    })
                    $(document).on('click', '#send_sms', function () {
                        let $btn = $(this);
                        $btn.prop('disabled', true);
                        let selected = getSelected('sms');
                        if (!selected.length) {
                            swal.fire('Ничего не выбрано');
                            $btn.prop('disabled', false);
                            return;
                        }
                        $.ajax({
                            url: $btn.data('url'),
                            data: {
                                friends: selected,
                                type: 'sms',
                            },
                            type: 'POST',
                            success: function (e) {
                                showMessage(e.message);
                                $btn.remove();
                            },
                            error: function (e) {
                                showErrors(e)
                                $btn.prop('disabled', false);
                            }
                        })
                    })
                    $(document).on('click', '.link_info', function () {
                        $('#show_info .modal-body').html('')
                        var link = $(this).data('link')
                        var type = $(this).data('type')
                        $.ajax({
                            url: link,
                            type: 'GET',
                            data: {type: type},
                            success: function (e) {
                                $('#show_info .modal-body').html(e.data)
                            },
                            error: function () {

                            }
                        })
                    })


                </script>
            @endif
        @endif
        <script>
            $(document).on('click', '.cl', function (e) {
                e.preventDefault();
                var num = $(this).data('star')
                $('#rate').val(num)
                $('.cl').each(function () {
                    var current = $(this).data('star');
                    if (current <= num) {
                        $(this).addClass('star-checked')
                    } else {
                        $(this).removeClass('star-checked')
                    }
                })
            })
        </script>
    @endpush
@endsection