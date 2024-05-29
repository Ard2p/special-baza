@extends('corpcustomer::layouts.global')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <div class="col-md-12 user-profile-wrap box-shadow-wrap">

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="errors" style="display: none">
                            <div class="alert alert-warning" id="alerts" role="alert">


                            </div>
                        </div>
                    </div>
                    <ol class="breadcrumb">
                        <li><a href="{{route('corp_index')}}">Главная</a></li>

                        <li class="active">Добавить банк</li>
                    </ol>
                    <form class="proposal-wrap" id="corp_brand_form" action="{{route('corp-banks.store')}}">
                        @csrf

                        <h3>Добавить банк</h3>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="required">Наименование</label>
                                <input class="promo_code"
                                       value=""
                                       name="name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Рассчетный счет</label>
                                <input class="promo_code"
                                       value=""
                                       name="account"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Бик</label>
                                <input class="promo_code"
                                       value=""
                                       name="bik"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Адрес</label>
                                <textarea class="promo_code"
                                          value=""
                                          name="address"
                                          type="text"></textarea>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                        <div class="col-md-12">
                            <div class="button">
                                <button type="submit" class="btn-custom">
                                    Создать
                                </button>
                            </div>
                        </div>


                    </form>

                </div>
            </div>
        </div>
    </div>
    @push('after-scripts')
        <script>
            $(document).on('submit', '#corp_brand_form', function (e) {
                e.preventDefault()
                $form = $(this);
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (r) {
                        showMessage(r.message),
                            setTimeout(function () {
                                location.href = r.url;
                            }, 2500)
                    },
                    error: function (e) {
                        showErrors(e)
                    }
                })
            })
        </script>
    @endpush
@endsection