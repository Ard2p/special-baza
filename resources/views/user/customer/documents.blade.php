@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>Документы</h1></div>
        </div>
        <div class="row">
            <div class="col-md-4 col-xs-12 col-lg-3"><!--left col-->

                @include('sections.info')

            </div>
            <div class="col-md-8 col-lg-9">
                <div class="clearfix"></div>
                <div class="search-wrap user-profile-wrap box-shadow-wrap document-wrap">
               {{--     @if(!Auth::user()->getActiveRequisite())
                        <div class="not-found-wrap">
                            <h3>Заполните, пожалуйста, ваши реквизиты</h3>
                            <div class="button">
                                <a href="/{{Auth::user()->getCurrentRoleName()}}/requisites" class="btn-custom black"
                                   data-toggle="modal">Заполнить реквизиты</a>
                            </div>
                        </div>
                    @elseif(!$documents->count())
                        <div class="not-found-wrap">
                            <h3>Договор в процессе подготовки</h3>
                        </div>
                    @elseif(Auth::user()->documents()->count())--}}
                        <h3>Фильтр</h3>
                        <div class="hr-line"></div>
                        <form id="documentFilters">
                            <div class="filter-list-wrap col-list two-cols">
                                <div class="col col-long">
                                    <div class="form-item">
                                        Тип
                                        <div class="custom-select-exp">
                                            <select name="type">
                                                <option value="">Выберите тип</option>
                                                @foreach(\App\Support\DocumentType::all() as $type)
                                                    <option value="{{$type->id}}">{{$type->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col col-long">
                                    <div class="form-item image-item end">
                                        <label for="date-picker-doc">
                                            Дата от
                                            <input type="text" id="date-picker-doc" name="date_from"data-toggle="datepicker"
                                                   placeholder="2018/08/08" autocomplete="off">
                                            <span class="image date"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col col-long">
                                    <div class="form-item image-item end">
                                        <label for="date-picker-doc-end">
                                            Дата до
                                            <input type="text" name="date_to" id="date-picker-doc-end" data-toggle="datepicker"
                                                   placeholder="2018/08/08" autocomplete="off">
                                            <span class="image date"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="btn-col">
                                    <div class="button">
                                        <button class="btn-custom">Поиск</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <h3>Документы</h3>
                        <div class="table-responsive adaptive-table">
                            <table id="documents_table" class="table table-striped table-bordered"
                                   style="width:100%">
                                <thead>
                                <tr>
                                    <th>Тип документа</th>
                                    <th>Номер</th>
                                    <th>Тип аккаунта</th>
                                    <th>Ссылка</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>

                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="list-proposals">
                            <h1>Документы</h1>
                            <div class="proposal-items">

                            </div>
                        </div>

                {{--    @endif--}}

                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->

    @include('scripts.documents.index')
@endsection

@push('after-scripts')
    <script>
        $(document).ready(function () {
            $('#tabs-panel a').click(function () {
                $('#tabs-panel a').removeClass('black')
                $(this).addClass('black')
            })
        })
    </script>
@endpush