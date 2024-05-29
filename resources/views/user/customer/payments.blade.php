@extends('layouts.main')
@section('content')
 {{--  <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>Платежи</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>
                <div class="search-wrap user-profile-wrap box-shadow-wrap">
                    <div class="button search-btns two-btn">
                        <a href="#fill" class="btn-custom black" data-id="1">Пополнить счет</a>
                        <a href="#get" class="btn-custom" data-id="2">Вывести средства</a>
                    </div>
                    <div id="tab1" class="active tab-list">
                        <div class="detail-search">
                            <div class="hr-line"></div>
                            @checkRequisite
                            <form id="in_transaction_form">
                                @csrf
                                <div class="col-md-offset-2 col-md-8">
                                    <div class="form-item">
                                        <label>Введите сумму для пополнения:</label>
                                        <input name="sum" value=""
                                               type="text">
                                    </div>
                                </div>
                                <input type="hidden" name="type" value="in">
                                <div class="col-md-offset-3 col-md-6">
                                    <div class="button">
                                        <button class="btn-custom" type="submit">Подтвердить</button>
                                    </div>
                                </div>

                            </form>
                            @else
                                <div class="not-found-wrap">
                                    <h3>Реквизиты не заполены. Чтобы пополнить счет заполните реквизиты</h3>
                                    <div class="button">
                                        <a href="/{{Auth::user()->getCurrentRoleName()}}/requisites"
                                           class="btn-custom black">Реквизиты</a>
                                    </div>
                                </div>
                                @endCheckRequisite
                        </div>
                    </div>
                    <div id="tab2" class="tab-list">
                        <div class="detail-search">
                            <div class="hr-line"></div>
                            @checkRequisite
                            <form id="out_transaction_form">
                                @csrf
                                <div class="col-md-offset-2 col-md-8">
                                    <div class="form-item">
                                        <label>Введите сумму для вывода:</label>
                                        <input name="sum" value=""
                                               type="text">
                                    </div>
                                </div>
                                <div class="col-md-offset-3 col-md-6">
                                    <div class="button">
                                        <button class="btn-custom" type="submit">Подтвердить</button>
                                    </div>
                                </div>
                                <input type="hidden" name="type" value="out">
                            </form>
                            @else
                                <div class="not-found-wrap">
                                    <h3>Реквизиты не заполены. Чтобы вывести средства заполните реквизиты</h3>
                                    <div class="button">
                                        <a href="/{{Auth::user()->getCurrentRoleName()}}/requisites"
                                           class="btn-custom black">Реквизиты</a>
                                    </div>
                                </div>
                                @endCheckRequisite
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
 --}}
 <!-- Modal -->
@endsection

@push('after-scripts')
    <script>
        $(document).ready(function () {
            $('#tabs-panel a').click(function () {
                $('#tabs-panel a').removeClass('black')
                $(this).addClass('black')
            })
            $('#in_transaction_form').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '/finance',
                    type: 'POST',
                    data: $('#in_transaction_form').serialize(),
                    success: function (data) {
                        showMessage(data.message);
                    },
                    error: function (message) {
                        showModalErrors(message)
                    }
                })
            })

            $('#out_transaction_form').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '/finance',
                    type: 'POST',
                    data: $('#out_transaction_form').serialize(),
                    success: function (data) {
                        showMessage(data.message);
                    },
                    error: function (message) {
                        showModalErrors(message)
                    }
                })
            })

        })
    </script>
@endpush