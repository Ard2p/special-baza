@extends('layouts.main')
@section('content')

    <div class="search-wrap">
        {{--<div class="button four-btns">

            @performer<a href="/contractor/machinery" class="btn-custom">Техника</a>@endPerformer

            @customer<a href="/customer/proposals" class="btn-custom">Заявки</a>@endCustomer
            @performer<a href="/proposals" class="btn-custom">Заявки</a>@endPerformer

            @customer<a href="/customer/orders" class="btn-custom">Заказы</a>@endCustomer
            @performer<a href="/orders" class="btn-custom">Заказы</a>@endPerformer

            <a href="/{{Auth::user()->getCurrentRoleName()}}/payments" class="btn-custom">Пополнить баланс</a>
        </div>--}}

        <div class="news-list" style="margin-top: 40px;">
            <h2 class="title">@lang('transbaza_home.news')</h2>
            @include('list')
        </div>

        @include('scripts.office')
        @endsection

        @push('after-scripts')
            <script>
                $(document).ready(function () {
                    // $('.search-btns > a').click(function (e) {
                    //     e.preventDefault();
                    //     $('.search-btns > a').removeClass('black')
                    //     $(this).addClass('black');
                    //     var idTab = '#tab' + $(this).data('id');
                    //     $('.tab-list').removeClass('active');
                    //     $(idTab).addClass('active')
                    // })
                })
            </script>
    @endpush