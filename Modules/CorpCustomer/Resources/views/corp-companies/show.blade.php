@extends('corpcustomer::layouts.global')

@section('content')


    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>Компания "{{$company->full_name}}"</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>

                <div class="col-md-offset-1 col-md-8 search-wrap user-profile-wrap box-shadow-wrap">
                    <ol class="breadcrumb">
                        <li><a href="{{route('corp_index')}}">Главная</a></li>
                        <li>
                            <a href="{{route('corp-brands.show', $company->brand->id)}}">{{$company->brand->full_name}}</a>
                        </li>

                        <li class="active">{{$company->full_name}}</li>
                    </ol>
                    <h3 class="title">Компания {{$company->full_name}}</h3>
                    <div class="machine-card">
                        <div class="list-params">
                            <p><strong>Полное наименование организации</strong>{{$company->full_name}}</p>
                            <p><strong>Сокращенное наименование организации</strong>{{$company->short_name}}</p>
                            <p><strong>Местонахождение</strong>{{$company->address}}</p>
                            <p><strong>Почтовый адрес организации</strong>{{$company->zip_code}}</p>
                            <p><strong>Контактный e-mail организации</strong>{{$company->email}}</p>
                            <p><strong>Контактный телефон организации</strong>{{$company->phone}}</p>
                            <p><strong>ИНН</strong>{{$company->inn}}</p>
                            <p><strong>КПП</strong>{{$company->kpp}}</p>
                            <p><strong>ОГРН</strong>{{$company->ogrn}}</p>

                        </div>
                    </div>
                    <h3>Сотрудники компании</h3>
                    @if($company->canEdit())
                        <div class="machinery-filter-wrap">
                            <div class="button">
                                <button type="button" class="btn-custom black" data-toggle="modal"
                                        data-target="#addEmployee">Добавить сотрудника
                                </button>
                            </div>
                        </div>
                    @endif
                    @if($company->employees->isNotEmpty())
                        <div id="employess">
                            @include('corpcustomer::corp-companies.employees')
                        </div>
                    @else
                        <div class="not-found-wrap">
                            <h3>У компании отсутствуют сотрудники </h3>
                        </div>
                    @endif
                    @if($company->banks->isNotEmpty())
                        <h3>Банковские реквизиты бренда</h3>
                        @foreach($company->banks as $bank)
                            <b>{{$bank->name}}</b>
                            <div class="machine-card">
                                <div class="list-params">
                                    <p><strong>Рассчетный счет</strong>{{$bank->account}}</p>
                                    <p><strong>БИК</strong>{{$bank->bik}}</p>
                                    <p><strong>Адрес</strong>{{$bank->address}}</p>

                                </div>
                            </div>
                            <hr>
                        @endforeach
                    @else
                        <div class="not-found-wrap">
                            <h3>Банковские реквизиты отсутствуют</h3>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="addEmployee" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="addEmplyee_form" action="{{route('add_employee')}}" role="form">
                    <input type="hidden" name="corp_company_id" value="{{$company->id}}">
                    @csrf
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Добавить сотрудника</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div>
                                <div class="col-md-12 requisites-popup-cols">
                                    <div class="form-item">
                                        <label>
                                            <input name="email"
                                                   placeholder="Email сотрудника в системе TRANSBAZA"
                                                   value="" type="text">
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label>
                                            <input name="position"
                                                   placeholder="Должность "
                                                   value="" type="text">
                                        </label>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer button two-btn">
                        <button type="button" class="btn-custom" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn-custom">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('after-scripts')
        <script>
            $(document).on('submit', '#addEmplyee_form', function (e) {
                let $form = $(this)
                e.preventDefault();
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (r) {
                        $('.modal').modal('hide')
                        showMessage(r.message)

                    },
                    error: function (e) {
                        showErrors(e)
                    }
                })
            })
        </script>
    @endpush
@stop
