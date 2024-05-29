<form action="{{route('company_requisite.store')}}" method="POST" id="requisite_form">
<div class="panel panel-default">
    <div class="panel-body">

            <input type="hidden" name="crm_company_id" value="{{$company->id}}">

            <div class="col-md-6">
                <div class="form-group">
                    <label>
                        Наименование ЮЛ </label>
                    <input name="name" class="form-control"
                           placeholder="Наименование ЮЛ"
                           value="{{$company->requisite->name ?? ''}}" type="text">

                </div>
                <div class="form-group">
                    <label>
                        ИНН </label>
                    <input name="inn"
                           class="form-control inn_entity"
                           required
                           placeholder="ИНН"
                           value="{{$company->requisite->inn ?? ''}}" type="number">

                </div>
                <div class="form-group">
                    <label>
                        КПП </label>
                    <input name="kpp"
                           placeholder="КПП"
                           class="form-control"
                           value="{{$company->requisite->kpp ?? ''}}" type="number">

                </div>
                <div class="form-group">
                    <label>
                        ОГРН </label>
                    <input name="ogrn"
                           class="form-control"
                           placeholder="ОГРН" class="ogrn_entity"
                           value="{{$company->requisite->ogrn ?? ''}}" type="number">

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>
                        ФИО генерального директора </label>
                    <input name="director"
                           class="form-control"
                           placeholder="ФИО генерального директора"
                           value="{{$company->requisite->director ?? ''}}" type="text">

                </div>
                <div class="form-group">
                    <label>
                        ФИО Главного бухгалтера </label>
                    <input name="booker"
                           class="form-control"
                           placeholder="ФИО Главного бухгалтера"
                           value="{{$company->requisite->booker ?? ''}}" type="text">

                </div>
                <div class="form-group">
                    <label>
                        Банк </label>
                    <input name="bank"
                           class="form-control"
                           placeholder="Банк"
                           value="{{$company->requisite->bank ?? ''}}" type="text">

                </div>
                <div class="three-items custom-items">
                    <div class="form-group">
                        <label>
                            Счет</label>
                        <input name="rs"
                               class="form-control"
                               placeholder="Счет" class="corr"
                               value="{{$company->requisite->rs ?? ''}}" type="text">

                    </div>
                    <div class="form-group">
                        <label>
                            Кор. счет </label>
                        <input name="ks"
                               class="form-control"
                               placeholder="Кор. счет"
                               value="{{$company->requisite->ks ?? ''}}" type="text">

                    </div>
                    <div class="form-group bik_length">
                        <label>
                            БИК </label>
                        <input name="bik"
                               class="form-control"
                               placeholder="БИК"
                               value="{{$company->requisite->bik ?? ''}}" type="text">

                    </div>

                </div>

            </div>

    </div>
</div>
<div class="clearfix"></div>
<div class="form-buttons panel-footer">
    <div role="group" class="btn-group">
        <button type="submit" class="btn btn-primary"><i
                    class="fa fa-check"></i> Сохранить
        </button>

        <button type="button"
                class="btn btn-danger btn-delete"><i class="fa fa-times"></i> Удалить
        </button>
    </div>
</div>
</form>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {

        $(document).on('submit','#requisite_form', function (e) {
            e.preventDefault()
            let $form = $(this)
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function (m) {
                    swal(m.message)
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }
            })
        })

    });
</script>