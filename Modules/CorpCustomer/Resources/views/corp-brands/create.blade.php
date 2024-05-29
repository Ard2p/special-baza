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

                        <li class="active">Создать бренд</li>
                    </ol>
                    <form class="proposal-wrap" id="corp_brand_form" action="{{route('corp-brands.store')}}">
                        @csrf

                        <h3>Новый бренд</h3>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="required">Полное наименование организации</label>
                                <input class="promo_code"
                                       value=""
                                       name="full_name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Сокращенное наименование организации (в соответствии с
                                    Уставом)</label>
                                <input class="promo_code"
                                       value=""
                                       name="short_name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Местонахождение (юридический адрес) организации </label>
                                <textarea class="promo_code"
                                          value=""
                                          name="address"
                                          type="text"></textarea>
                            </div>
                            <div class="form-item">
                                <label class="required">Почтовый адрес организации</label>
                                <input class="promo_code"
                                       value=""
                                       name="zip_code"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Контактный e-mail организации</label>
                                <input class="promo_code"
                                       value=""
                                       name="email"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Контактный телефон организации;</label>
                                <input class="promo_code"
                                       value=""
                                       name="phone"
                                       type="text">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="required">ИНН</label>
                                <input class="promo_code"
                                       value=""
                                       name="inn"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">КПП</label>
                                <input class="promo_code"
                                       value=""
                                       name="kpp"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">ОГРН</label>
                                <input class="promo_code"
                                       value=""
                                       name="ogrn"
                                       type="text">
                            </div>
                            @if($banks->count())
                                <div class="form-item">
                                    <b>Укажите банки для этого бренда:</b>
                                    @foreach($banks as $bank)
                                        <label for="checked-input{{$bank->id}}" class="checkbox">
                                            {{$bank->name}}
                                            <input type="checkbox" name="banks[]" value="{{$bank->id}}"
                                                   id="checked-input{{$bank->id}}">
                                            <span class="checkmark"></span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                У вас отстутсвует информация о банках. Добавить ее можно на главной странице.
                            @endif
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