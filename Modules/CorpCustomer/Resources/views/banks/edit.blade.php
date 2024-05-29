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

                        <li class="active">Изменить банк</li>
                    </ol>
                    <form class="proposal-wrap" id="corp_brand_form" action="{{route('corp-banks.update', $bank->id)}}">
                        @csrf
                        @method('PATCH')
                        <h3>Изменить банк</h3>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="required">Наименование</label>
                                <input class="promo_code"
                                       value="{{$bank->name}}"
                                       name="name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Рассчетный счет</label>
                                <input class="promo_code"
                                       value="{{$bank->account}}"
                                       name="account"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Бик</label>
                                <input class="promo_code"
                                       value="{{$bank->bik}}"
                                       name="bik"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Адрес</label>
                                <textarea class="promo_code"
                                          name="address"
                                          type="text">{{$bank->address}}</textarea>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                        <div class="col-md-12">
                            <div class="button">
                                <button type="submit" class="btn-custom">
                                    Изменить
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