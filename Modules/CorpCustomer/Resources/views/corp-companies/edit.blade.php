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
                        <li><a href="{{route('corp-brands.show', $company->brand->id)}}">{{$company->brand->full_name}}</a></li>
                        <li class="active">Изменить компанию</li>
                    </ol>
                    <form class="proposal-wrap" id="corp_brand_form" action="{{route('corp-companies.update', $company->id)}}">
                        @csrf
                           @method('PATCH')
                        <h3>{{$company->full_name}}</h3>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="required">Полное наименование организации</label>
                                <input class="promo_code"
                                       value="{{$company->full_name}}"
                                       name="full_name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Сокращенное наименование организации (в соответствии с
                                    Уставом)</label>
                                <input class="promo_code"
                                       value="{{$company->short_name}}"
                                       name="short_name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Местонахождение (юридический адрес) организации </label>
                                <textarea class="promo_code"
                                          name="address"
                                          type="text">{{$company->address}}</textarea>
                            </div>
                            <div class="form-item">
                                <label class="required">Почтовый адрес организации</label>
                                <input class="promo_code"
                                       value="{{$company->zip_code}}"
                                       name="zip_code"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Контактный e-mail организации</label>
                                <input class="promo_code"
                                       value="{{$company->email}}"
                                       name="email"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label class="required">Контактный телефон организации;</label>
                                <input class="promo_code phone"
                                       value="{{$company->phone}}"
                                       name="phone"
                                       type="text">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="required">ИНН</label>
                                <input class="promo_code"
                                       value="{{$company->inn}}"
                                       name="inn"
                                       type="number">
                            </div>
                            <div class="form-item">
                                <label class="required">КПП</label>
                                <input class="promo_code"
                                       value="{{$company->kpp}}"
                                       name="kpp"
                                       type="number">
                            </div>
                            <div class="form-item">
                                <label class="required">ОГРН</label>
                                <input class="promo_code"
                                       value="{{$company->ogrn}}"
                                       name="ogrn"
                                       type="text">
                            </div>
                            @if($banks->count())
                                <div class="form-item">
                                    <b>Укажите банки для этой компании:</b>
                                    @foreach($banks as $bank)
                                        <label for="checked-input{{$bank->id}}" class="checkbox">
                                            {{$bank->name}}
                                            <input type="checkbox" name="banks[]" value="{{$bank->id}}"
                                                   id="checked-input{{$bank->id}}" {{$company->banks->contains($bank) ? 'checked' : ''}}>
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
                        showMessage(r.message)
                    },
                    error: function (e) {
                        showErrors(e)
                    }
                })
            })
        </script>
    @endpush
@endsection