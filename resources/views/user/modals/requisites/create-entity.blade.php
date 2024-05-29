<div class="modal" id="entityModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="form-horizontal" id="entityForm" role="form">
                @csrf @if($requisite)
                    <input type="hidden" value="{{$requisite->id}}" name="entity_id">
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
                            <div class="col-md-12 requisites-popup-cols">
                                <div class="form-item">
                                    <label>
                                        <input name="name"
                                               placeholder="Наименование ЮЛ"
                                               value="{{$requisite->name ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="three-items">
                                    <div class="form-item">
                                        <label>
                                            <input name="inn" class="inn_entity"
                                                   required
                                                   placeholder="ИНН"
                                                   value="{{$requisite->inn ?? ''}}" type="number">
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label>
                                            <input name="kpp"
                                                   placeholder="КПП"
                                                   class="kpp_entity"
                                                   value="{{$requisite->kpp ?? ''}}" type="number">
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label>
                                            <input name="ogrn"
                                                   placeholder="ОГРН" class="ogrn_entity"
                                                   value="{{$requisite->ogrn ?? ''}}" type="number">
                                        </label>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <label>
                                        <input name="director"
                                               placeholder="ФИО генерального директора"
                                               value="{{$requisite->director ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label>
                                        <input name="booker"
                                               placeholder="ФИО Главного бухгалтера"
                                               value="{{$requisite->booker ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label>
                                        <input name="bank"
                                               placeholder="Банк"
                                               value="{{$requisite->bank ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="three-items custom-items">
                                    <div class="form-item">
                                        <label>
                                            <input name="rs"
                                                   placeholder="Счет" class="corr"
                                                   value="{{$requisite->rs ?? ''}}" type="text">
                                        </label>
                                    </div>
                                    <div class="form-item">
                                        <label>
                                            <input name="ks"
                                                   class="corr"
                                                   placeholder="Кор. счет"
                                                   value="{{$requisite->ks ?? ''}}" type="text">
                                        </label>
                                    </div>
                                    <div class="form-item bik_length">
                                        <label>
                                            <input name="bik"
                                                   placeholder="БИК"
                                                   value="{{$requisite->bik ?? ''}}" type="text">
                                        </label>
                                    </div>
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