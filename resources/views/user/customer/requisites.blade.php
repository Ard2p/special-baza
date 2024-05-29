@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>Мои реквизиты</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>
                <div class="search-wrap user-profile-wrap box-shadow-wrap">
                    <div class="button search-btns two-btn">

                        @if(Auth::user()->getActiveRequisite())

                            @if(Auth::user()->getActiveRequisiteType() == 'entity')
                            <a href="#entity" class="btn-custom black" data-id="1">Юридическое лицо</a>
                            @else
                            @customer
                            <a href="#individual" class="btn-custom black" data-id="2">Физическое лицо</a>
                            @endCustomer
                            @endif
                        @elseif(!Auth::user()->getActiveRequisite())
                            <a href="#entity" class="btn-custom black" data-id="1">Юридическое лицо</a>
                            @customer
                            <a href="#individual" class="btn-custom" data-id="2">Физическое лицо</a>
                            @endCustomer
                        @endif
                    </div>
                    @if((Auth::user()->getActiveRequisite() && Auth::user()->getActiveRequisiteType() == 'entity') || !Auth::user()->getActiveRequisite())
                    <div id="tab1"
                         class=" {{(Auth::user()->getActiveRequisiteType() == 'entity' || !Auth::user()->getActiveRequisite()) ? 'active' : ''}} tab-list">
                        <div class="detail-search">
                            @if($entities->count())
                                @if(!Auth::user()->getActiveRequisite())
                                    <div class="button-requisites">
                                        <div class="button">
                                            <a href="#" class="btn-custom" data-toggle="modal"
                                               data-target="#entityModal">Добавить</a>
                                        </div>
                                    </div>
                                @endif
                                <hr>
                                <table class="table table-striped table-bordered adaptive-table"
                                       style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>№</th>
                                        <th>Наименование</th>
                                        <th>Дата создания</th>
                                        <th>Статус</th>
                                        <th style="text-align: center"><em class="fa fa-cog"></em></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($entities as $entity)
                                        <tr>
                                            <td>{{$entity->id}}</td>
                                            <td>{{$entity->name}}</td>
                                            <td>{{$entity->created_at}}</td>
                                            <td>{{$entity->active ? "Активен" : 'Не активен'}}</td>
                                            <td>
                                                <a class="btn-machinaries edit-entity" href="#" data-id="{{$entity->id}}"><i class="fas fa-file-signature"></i></a>
                                                {{--<a class="btn-machinaries"  href="#" data-id="{{$entity->id}}"><i class="show"></i></a>--}}
                                                <a class="btn-machinaries deleteEntity" data-id="{{$entity->id}}" href="#"><i class="fas fa-broom"></i></a>
                                                {{--<a class="" href="/entity_requisite/{{$entity->id}}">Просмотр</a>--}}
                                            </td>
                                        </tr>
                                    @endforeach

                                    </tbody>
                                </table>

                                    <div class="list-proposals">
                                        <h1>Список реквизитов</h1>
                                        <div class="proposal-items full-items">
                                            @foreach($entities as $entity)
                                                <div class="item">
                                                    <p class="region">
                                                        <strong>ID:</strong>
                                                        {{$entity->id}}
                                                    </p>
                                                    <p class="type">
                                                        <strong>Имя:</strong>
                                                        {{$entity->name}}
                                                    </p>
                                                    <p class="budget">
                                                        <strong>Статус:</strong>
                                                        {{$entity->active ? "Активен" : 'Не активен'}}
                                                    </p>
                                                    <p class="date">
                                                        <strong>Дата создания: </strong>
                                                        {{$entity->created_at->format('d.m.Y H:i:s')}}
                                                    </p>

                                                    <div class="button">
                                                        {{--<a  href="#" data-id="{{$entity->id}}" class="btn-custom">Просмотр</a>--}}
                                                        <a href="#" data-id="{{$entity->id}}" class="btn-custom edit-entity">Редактировать</a>
                                                        <a href="#" class="btn-custom deleteEntity" data-id="{{$entity->id}}">Удалить</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                            @else
                                <div class="not-found-wrap">
                                    <h3>У вас отсутствуют реквизиты ЮЛ!</h3>
                                    <div class="button">
                                        <a href="#" class="btn-custom black" data-toggle="modal"
                                           data-target="#entityModal">Добавить
                                            реквизиты</a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    @customer
                    @if((Auth::user()->getActiveRequisite() && Auth::user()->getActiveRequisiteType() == 'individual') || !Auth::user()->getActiveRequisite())
                    <div id="tab2" class="{{Auth::user()->getActiveRequisiteType() == 'individual' ? 'active' : ''}} tab-list">
                        <div class="detail-search">
                            @if($individuals->count())
                                @if(!Auth::user()->getActiveRequisite())
                                    <div class="button-requisites">
                                        <div class="button">
                                            <a href="#" class="btn-custom" data-toggle="modal"
                                               data-target="#individualModal">Добавить</a>
                                        </div>
                                    </div>
                                @endif
                                <hr>
                                <table class="table table-striped table-bordered adaptive-table"
                                       style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>№</th>
                                        <th>Ф.И.О</th>
                                        <th>Дата создания</th>
                                        <th>Статус</th>
                                        <th style="text-align: center"><em class="fa fa-cog"></em></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($individuals as $individual)
                                        <tr>
                                            <td data-label="Регион: ">{{$individual->id}}</td>
                                            <td data-label="Наименование: ">{{$individual->surname}} {{$individual->firstname}} {{$individual->middlename}}</td>
                                            <td data-label="Наименование: ">{{$individual->created_at}}</td>
                                            <td data-label="Наименование: ">{{$individual->active ? "Активен" : 'Не активен'}}</td>
                                            <td>
                                                <a class="btn-machinaries edit-individual" href="#" data-id="{{$individual->id}}"><i class="fas fa-file-signature"></i></a>
                                                {{--<a class="btn-machinaries"  href="#" data-id="{{$individual->id}}"><i class="show"></i></a>--}}
                                                <a class="btn-machinaries deleteIndividual" data-id="{{$individual->id}}"><i class="fas fa-broom"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach

                                    </tbody>
                                </table>
                                    <div class="list-proposals">
                                        <h1>Список реквизитов</h1>
                                        <div class="proposal-items full-items">
                                            @foreach($individuals as $individual)
                                                <div class="item">
                                                    <p class="region">
                                                        <strong>ID:</strong>
                                                        {{$individual->id}}
                                                    </p>
                                                    <p class="type">
                                                        <strong>Ф.И.О:</strong>
                                                        {{$individual->surname}} {{$individual->firstname}} {{$individual->middlename}}
                                                    </p>
                                                    <p class="budget">
                                                        <strong>Статус:</strong>
                                                        {{$individual->active ? "Активен" : 'Не активен'}}
                                                    </p>
                                                    <p class="date">
                                                        <strong>Дата создания: </strong>
                                                        {{$individual->created_at->format('d.m.Y H:i:s')}}
                                                    </p>

                                                    <div class="button">
                                                        {{--<a  href="#" data-id="{{$individual->id}}" class="btn-custom">Просмотр</a>--}}
                                                        <a href="#" data-id="{{$individual->id}}" class="btn-custom edit-individual">Редактировать</a>
                                                        <a href="#" class="btn-custom deleteIndividual" data-id="{{$individual->id}}">Удалить</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                            @else
                                <div class="not-found-wrap">
                                    <h3>У вас отсутствуют реквизиты ФЛ!</h3>
                                    <div class="button">
                                        <a href="#" class="btn-custom black" data-toggle="modal"
                                           data-target="#individualModal">Добавить реквизиты</a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endCustomer

                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->

    @include('user.modals.requisites.create-entity')
    @include('user.modals.requisites.create-individual')
    @include('scripts.requisites.index')
@endsection

@push('after-scripts')
    <script>
        $(document).ready(function () {
            $('#tabs-panel a').click(function () {
                $('#tabs-panel a').removeClass('black')
                $(this).addClass('black')
            })

            $('.edit-entity').click(function (e) {
                e.preventDefault();
                var entityId = $(this).data('id');
                var entities = {!! json_encode($entities ?? []) !!}
                entities.forEach(function (entity) {
                    if (entity.id == entityId) {
                        for (var key in entity) {
                            $('#entityForm [name="' + key + '"]').val(entity[key])
                        }
                        var hiddenInput = '<input type="hidden" name="entity_id" value="' + entity.id + '">'
                        $('#entityForm').append(hiddenInput)
                        $('#entityModal').modal('show')

                    }
                })
            })

            $('.edit-individual').click(function (e) {
                e.preventDefault();
                var individualId = $(this).data('id');
                var individuals = {!! json_encode($individuals ?? []) !!}
                individuals.forEach(function (individual) {
                    if (individual.id == individualId) {
                        console.log(individual)
                        for (var key in individual) {
                            $('#individualForm [name="' + key + '"]').val(individual[key])
                        }
                        var hiddenInput = '<input type="hidden" name="individual_id" value="' + individual.id + '">'
                        $('#individualForm').append(hiddenInput)
                        $('#individualModal').modal('show')

                    }
                })
            })

        })
    </script>
@endpush