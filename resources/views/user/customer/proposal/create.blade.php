@extends('layouts.main')
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
                    <form id="proposal_form" class="proposal-wrap">
                        <div class="col-md-6">
                            <h3>Новая заявка</h3>
                            <p><strong>Категория техники:</strong>{{$types}}
                            </p>
                            <p><strong>Марка
                                    техники:</strong>
                            </p>
                            <p><strong>Адрес выполнения работ: </strong>
                            <span id="addressData">{{\App\Support\Region::findOrFail($request->input('region'))->name}},
                                {{\App\City::findOrFail($request->input('city_id'))->name}},
                                {{$request->input('address')}}</span>
                            </p>
                            <p><strong>Дата
                                    выполнения:</strong> {{$service->getStartDate()->format('d/m/Y')}}
                            </p>
                            <p><strong>Колличество смен:</strong> {{$service->getDays()}}</p>

                            @csrf
                            @foreach($request->except('_token', 'sum') as $name => $value)
                                <input type="hidden" name="{{$name}}" value="{{$value}}">
                            @endforeach
                            <div class="form-item col-md-6" style="margin-top: 25px;">
                                <label class="required">
                                    Бюджет (руб)
                                    <input type="text" name="sum" value="{{$request->input('sum')}}" rows="3">
                                </label>
                            </div>
                            <div class="form-item col-md-6" style="margin-top: 25px;">
                                <label>
                                    Комментарий к заказу
                                    <textarea name="comment" rows="3"></textarea>
                                </label>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <div id="map" class="row clearfix body" style="height: 250px;"></div>
                            <hr>
                            <div class="button">
                                <button type="submit" id="create_proposal" class="btn-custom">
                                    Сформировать новую заявку
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="col-md-6">

                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('scripts.proposal.create')
@endsection