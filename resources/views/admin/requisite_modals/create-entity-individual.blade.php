<div class="modal" id="entityIndividualModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form class="form-horizontal" id="entityIndividualForm" role="form">
                <input type="hidden" value="{{$user->id}}" name="user_id">
                <input type="hidden" value="customer" name="req_type">
                @csrf @if($requisite_customer)
                    <input type="hidden" value="{{$requisite_customer->id}}" name="entity_id">
                @endif
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Реквизиты
                        ЮЛ {{Auth::user()->getActiveRequisiteType() == 'entity' ? 'Исполнителя' : 'Заказчика'}}</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div>

                            <input type="hidden" name="entity" value="1">
                            <div class="col-md-12">

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            Наименование ЮЛ
                                            <input name="name" class="form-control"
                                                   placeholder="Наименование ЮЛ"
                                                   value="{{$requisite_customer->name ?? ''}}" type="text">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            ИНН
                                            <input name="inn"
                                                   class="form-control inn_entity"
                                                   required
                                                   placeholder="ИНН"
                                                   value="{{$requisite_customer->inn ?? ''}}" type="number">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            КПП
                                            <input name="kpp"
                                                   placeholder="КПП"
                                                   class="form-control"
                                                   value="{{$requisite_customer->kpp ?? ''}}" type="number">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            ОГРН
                                            <input name="ogrn"
                                                   class="form-control"
                                                   placeholder="ОГРН"
                                                   value="{{$requisite_customer->ogrn ?? ''}}" type="number">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            ФИО генерального директора
                                            <input name="director"
                                                   class="form-control"
                                                   placeholder="ФИО генерального директора"
                                                   value="{{$requisite_customer->director ?? ''}}" type="text">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            ФИО Главного бухгалтера
                                            <input name="booker"
                                                   class="form-control"
                                                   placeholder="ФИО Главного бухгалтера"
                                                   value="{{$requisite_customer->booker ?? ''}}" type="text">
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            Банк
                                            <input name="bank"
                                                   class="form-control"
                                                   placeholder="Банк"
                                                   value="{{$requisite_customer->bank ?? ''}}" type="text">
                                        </label>
                                    </div>
                                    <div class="three-items custom-items">
                                        <div class="form-group">
                                            <label>
                                                Счет
                                                <input name="rs"
                                                       class="form-control"
                                                       placeholder="Счет"
                                                       value="{{$requisite_customer->rs ?? ''}}" type="text">
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <label>
                                                Кор. счет
                                                <input name="ks"
                                                       class="form-control"
                                                       placeholder="Кор. счет"
                                                       value="{{$requisite_customer->ks ?? ''}}" type="text">
                                            </label>
                                        </div>
                                        <div class="form-group bik_length">
                                            <label>
                                                БИК
                                                <input name="bik"
                                                       class="form-control"
                                                       placeholder="БИК"
                                                       value="{{$requisite_customer->bik ?? ''}}" type="text">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer button two-btn">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>