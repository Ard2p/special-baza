<div class="modal" id="individualModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document" >
        <div class="modal-content"  style="height: 560px; overflow: scroll;">
            <form class="form-horizontal" id="individualForm" role="form">
                @csrf
                @if($requisite)
                    <input type="hidden" value="{{$requisite->id}}" name="individual_id">
                @endif

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Реквизиты ФЛ Заказчика (паспорт)</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 requisites-popup-cols">
                            <div class="three-items">
                                <div class="form-item">
                                    <label>
                                        <input name="surname"
                                               placeholder="Фамилия"
                                               value="{{$requisite->surname ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label>
                                        <input name="firstname"
                                               placeholder="Имя"
                                               value="{{$requisite->firstname ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-item">
                                    <label>
                                        <input name="middlename"
                                               placeholder="Отчество"
                                               value="{{$requisite->middlename ?? ''}}" type="text">
                                    </label>
                                </div>
                            </div>
                            <div class="two-items">
                                <div class="form-item image-item end">
                                    <label for="date">
                                        <input name="birth_date"
                                               placeholder="Дата рождения"
                                               id="date" autocomplete="off" data-toggle="datepicker"
                                               @isset($requisite->birth_date ) value="{{$requisite->birth_date->format('Y/m/d') ?? ''}}"
                                               @endisset type="text">
                                        <span class="image date"></span>
                                    </label>
                                </div>
                                <div class="form-item two-checkbox">
                                    <p class="title">Пол</p>
                                    <label for="checked-gender-m" class="radio">
                                        <input type="radio" id="checked-gender-m" name="gender"
                                               value="m"@isset($requisite->gender) {{$requisite->gender ? '' : 'checked'}} @endisset >Мужской
                                        <span class="checkmark"></span>
                                    </label>
                                    <label for="checked-gender-w" class="radio">
                                        <input type="radio" id="checked-gender-w" name="gender"
                                               value="w" @isset($requisite->gender) {{$requisite->gender ? 'checked' : ''}} @endisset >Женский
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                            </div>
                           {{-- <div class="form-item">
                                <label>
                                    <input name="inn"
                                           class="inn_ip"
                                           placeholder="ИНН"
                                           value="{{$requisite->inn ?? ''}}"
                                           type="text">
                                </label>
                            </div>--}}
                            <div class="form-item">
                                <label>
                                    <input name="passport_number"
                                           placeholder="Номер паспорта"
                                           value="{{$requisite->passport_number ?? ''}}"
                                           type="text">
                                </label>
                            </div>
                            <div class="form-item">
                                <label>
                                    <input name="issued_by"
                                           placeholder="Орган выдачи паспорта"
                                           value="{{$requisite->issued_by ?? ''}}" type="text">
                                </label>
                            </div>
                            <div class="form-item">
                                <label>
                                    <input name="passport_date"
                                           placeholder="Дата выдачи паспорта"
                                           data-toggle="datepicker"
                                           value="{{$requisite->passport_date ?? ''}}" type="text">
                                </label>
                            </div>
                            <div class="form-item">
                                <label>
                                    <input name="kp"
                                           placeholder="Код подразделения"
                                           value="{{$requisite->kp ?? ''}}" type="text">
                                </label>
                            </div>
                            <div class="form-item">
                                <label>
                                    <input name="register_address"
                                           placeholder="Адрес регистрации"
                                           value="{{$requisite->register_address ?? ''}}"
                                           type="text">
                                </label>
                            </div>
                            <div class="col roller-item">
                                <div class="item">
                                    <h4>Сканы документов</h4>
                                </div>
                                <div class="content">
                                    <helper-image-loader multiple-data="1" col-id="scsnsTech" col-name="scans[]"
                                                         :url="{{json_encode(route('machinery.load-files'))}}"
                                                         @if($requisite && isset($requisite->scans))
                                                         :exist="{{$requisite->scans}}"
                                                         @endif
                                                         :token="{{json_encode(csrf_token())}}"></helper-image-loader>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <input type="hidden" name="individual" value="1">

                {{--<div class="form-group">--}}
                {{--<div class="col-lg-12">--}}
                {{--<div class="col-md-offset-7 col-md-3 button">--}}
                {{--<button type="submit" class="btn-custom">Сохранить</button>--}}
                {{--</div>--}}
                {{--</div>--}}
                {{--</div>--}}
                <div class="modal-footer button two-btn">
                    <button type="button" class="btn-custom" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn-custom">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>