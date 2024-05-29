<div class="modal" id="individualModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="height: 560px; overflow: scroll;">
            <form class="form-horizontal" id="individualForm" role="form">
                @csrf
                <input type="hidden" value="{{$user->id}}" name="user_id">
                @if($requisite_customer)
                    <input type="hidden" value="{{$requisite_customer->id}}" name="individual_id">
                @endif

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Реквизиты ФЛ Заказчика (паспорт)</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 ">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        Фамилия
                                        <input name="surname"
                                               class="form-control"
                                               placeholder="Фамилия"
                                               value="{{$requisite_customer->surname ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        Имя
                                        <input name="firstname"
                                               class="form-control"
                                               placeholder="Имя"
                                               value="{{$requisite_customer->firstname ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        Отчество
                                        <input name="middlename"
                                               class="form-control"
                                               placeholder="Отчество"
                                               value="{{$requisite_customer->middlename ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="date">
                                        Дата рождения
                                        <input name="birth_date"
                                               class="form-control"
                                               placeholder="Дата рождения"
                                               id="date" autocomplete="off" data-toggle="datepicker"
                                               @isset($requisite_customer->birth_date ) value="{{$requisite_customer->birth_date->format('Y/m/d') ?? ''}}"
                                               @endisset type="text">
                                        <span class="image date"></span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        Номер паспорта
                                        <input name="passport_number"
                                               placeholder="Номер паспорта"
                                               class="form-control"
                                               value="{{$requisite_customer->passport_number ?? ''}}"
                                               type="text">
                                    </label>
                                </div>
                            </div>
                            {{-- <div class="form-item">
                                 <label>
                                     <input name="inn"
                                            class="inn_ip"
                                            placeholder="ИНН"
                                            value="{{$requisite_customer->inn ?? ''}}"
                                            type="text">
                                 </label>
                             </div>--}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="checked-gender-m" class="radio-inline">
                                        <input type="radio" id="checked-gender-m" name="gender"
                                               class=""
                                               value="m"@isset($requisite_customer->gender) {{$requisite_customer->gender ? '' : 'checked'}} @endisset >Мужской
                                    </label>
                                    <label for="checked-gender-w" class="radio-inline">
                                        <input type="radio" id="checked-gender-w" name="gender"
                                               value="w" @isset($requisite_customer->gender) {{$requisite_customer->gender ? 'checked' : ''}} @endisset >Женский
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>
                                        Орган выдачи паспорта
                                        <input name="issued_by"
                                               placeholder="Орган выдачи паспорта"
                                               class="form-control"
                                               value="{{$requisite_customer->issued_by ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        Дата выдачи паспорта
                                        <input name="passport_date"
                                               placeholder="Дата выдачи паспорта"
                                               class="form-control"
                                               data-toggle="datepicker"
                                               value="{{$requisite_customer->passport_date ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        Код подразделения
                                        <input name="kp"
                                               placeholder="Код подразделения"
                                               class="form-control"
                                               value="{{$requisite_customer->kp ?? ''}}" type="text">
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        Адрес регистрации
                                        <input name="register_address"
                                               class="form-control"
                                               placeholder="Адрес регистрации"
                                               value="{{$requisite_customer->register_address ?? ''}}"
                                               type="text">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="col roller-item">
                                    <div class="item">
                                        <h4>Сканы документов</h4>
                                    </div>
                                    <helper-image-loader multiple-data="1" col-id="scsnsTech" col-name="scans[]"
                                                         :url="{{json_encode(route('load-file'))}}"
                                                         :multipleData="{{json_encode(1)}}"
                                                         @if($requisite_customer && isset($requisite_customer->scans))
                                                         :exist="{{$requisite_customer->scans}}"
                                                         @endif
                                                         :token="{{json_encode(csrf_token())}}">

                                    </helper-image-loader>
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
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/x-template" id="img-template">
    <div class="image-load">
        <div class="button" v-if="multipleData == 0 && imagesArr.length < 1 || multipleData == 1">
            <label :for="colId">
                <span class="btn btn-primary" style="padding: 0 20px;">Добавить фото</span>
                <input type="file" :id="colId" style="display: none;" multiple v-if="multipleData == 1"
                       @change="loadPhoto">
                <input type="file" :id="colId" style="display: none;" @change="loadPhoto" v-else>
            </label>
        </div>
        <input type="hidden" :name="colName" :value="image" v-for="image in imagesArr"
               v-if="imagesArr && imagesArr.length">
        <input type="hidden" :name="colName" v-if="required && (!imagesArr || !imagesArr.length)">

        <div class="row fancy-container">
            <div class="col-md-4" v-for="(image, i) in imagesArr" v-if="imagesArr">
                <span style="
        cursor: pointer;
        position: absolute;
        top: 5px;
        right: 20px;
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #fff url(/images/close-button.png?16c5351918764f63a0c2af110c1530bc) no-repeat center;
        background-size: 10px 10px;"
                      class="delete" @click="delImage(i)"></span>
                <a class="thumbnail fancybox" rel="ligthbox" :href="'/' + image">
                    <img class="img-responsive" alt="" :src="'/' + image"/>
                </a>
            </div>
        </div>
    </div>
</script>
