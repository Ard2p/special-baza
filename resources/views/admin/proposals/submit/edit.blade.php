<div class="content body" id="tables_content">
    <script>
        var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    </script>
    @if(Session::has('errors'))
        <div class="alert alert-warning">
            <strong>Внимание!</strong>{!! Session::get('errors') !!}
        </div>
    @endif
    @if(Session::has('success'))
    <div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-hide="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        {{Session::get('success')}}
    </div>
    @endif
    <div class="panel">
        <div class="panel-body">
            <ol class="breadcrumb">

                <li><a href="{{route('submit-proposal.index')}}"> Все заяки</a></li>
                <li class="active">Консультация пользователя</li>
            </ol>
            <h3>Консультация пользователя #{{$form->user->id}} {{$form->user->email}} {{$form->user->phone}}</h3>
            <div class="row">

                <div class="col-md-12">

                    <div class="col-md-6">
                        {{--<div class="form-group">
                            <label>Email</label>
                            <input class="form-control" type="text" value="{{$form->email}}" disabled>
                        </div>


                        <div class="form-group">
                            <label>Телефон</label>
                            <input class="form-control" type="text" value="{{$form->phone}}" disabled>
                        </div>--}}
                        <div class="form-group">
                            <label>Комментарий</label>
                            <textarea class="form-control" type="text" disabled>{{$form->comment}}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Категория</label>
                            <input class="form-control" value="{{$form->category->name ?? 'Не указано'}}" type="text"
                                   disabled>
                        </div>
                        <div class="form-group">
                            <label>Регион</label>
                            <input class="form-control" value="{{$form->region->name ?? 'Не указано'}}" type="text"
                                   disabled>
                        </div>
                        <div class="form-group">
                            <label>Город</label>
                            <input class="form-control" value="{{$form->city->name ?? 'Не указано'}}" type="text"
                                   disabled>
                        </div>
                        <div class="form-group input-group">
                            <label>URL</label>
                            <input class="form-control" type="text" value="{{$form->url}}" disabled>
                            <div class="input-group-btn">

                                <a target="_blank" class="btn btn-default" href="{!!  $form->url !!}" style="    margin-top: 25px;">Перейти</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="btn-group">
                                <a class="btn btn-primary" target="_blank" href="/users/{{$form->user->id}}/edit">Профиль</a>
                                <button class="btn btn-success" type="button">Позвонить</button>
                                <a class="btn btn-info"  data-toggle="collapse" href="#collapse1">Написать письмо</a>

                            </div>
                        </div>
                        <div class="panel-group">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">

                                    </h4>
                                </div>
                                <div id="collapse1" class="panel-collapse collapse">
                                    <form action="{{route('send_user_email')}}" method="post">
                                        @csrf
                                        <div class="panel-body">

                                            <input type="hidden" name="email" value="{{$form->user->email}}">
                                            <div class="form-elements">
                                                <div class="form-group">
                                                    <label>Тема письма</label>
                                                    <input class="form-control" type="text" name="name" value="{{old('name')}}">
                                                </div>
                                                <div class="form-group form-element-wysiwyg ">
                                                    <label for="content" class="control-label">
                                                        Письмо
                                                    </label>
                                                    <textarea id="letter" name="letter" cols="50"
                                                              rows="10">{{old('letter')}}</textarea>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="form-buttons panel-footer">
                                            <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Отправить
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Последняя активность</label>
                            <input class="form-control" value="{{$form->user->last_activity}}" type="text" disabled>
                        </div>
                        <div class="form-group">
                            <label>Дата</label>
                            <input class="form-control" value="{{$form->created_at}}" type="text" disabled>
                        </div>

                        <div class="form-group">
                            <label>Регион пользователя</label>
                            <input class="form-control" value="{{$form->user->region_name ?: 'Не указано'}}" type="text"
                                   disabled>
                        </div>
                        <div class="form-group">
                            <label>Город пользователя</label>
                            <input class="form-control" value="{{$form->user->city_name ?: 'Не указано'}}" type="text"
                                   disabled>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox"  value="1" {{($form->user->email_confirm) ? 'checked' : ''}} disabled>Подтвержден Email
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox"  value="1" {{$form->user->phone_confirm ? 'checked' : ''}} disabled>Подтвержден Телефон
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            @if($form->proposal_id === 0)
                <form class="row" method="POST" id="create_proposal"
                      action="{{route('submit-proposal.update', $form->id)}}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="user_id" value="{{$form->user_id}}">
                    <input type="hidden" name="machine_type" value="machine">
                    <div class="col-md-12">
                        <h3>Создать заявку</h3>
                        <div class="col-md-6">
                            <div class="form-group form-element-dependentselect">
                                <label class="control-label">Марка техники</label>
                                <select style="width:100%;" class="form-control input-select column-filter"
                                        name="brand">
                                    <option value="0">Не указана</option>
                                    @foreach($brands as $brand)
                                        <option value="{{$brand->id}}">{{$brand->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form-element-dependentselect">
                                <label class="control-label">Категория техники</label>
                                <select style="width:100%;" class="form-control input-select column-filter"
                                        name="type">
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group form-element-dependentselect">
                                <label class="control-label">Регион</label>
                                <select style="width:100%;"
                                        class="form-control input-select column-filter input-select-dependent"
                                        id="region_id"
                                        data-select-type="single"
                                        {{--   data-url="https://office.transbaza.com/machineries/dependent-select/region_id/3"--}}
                                        data-depends="[]"
                                        name="region">
                                    <option>Выберите регион</option>
                                    @foreach($regions as $region)
                                        <option value="{{$region->id}}">{{$region->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group form-element-dependentselect ">
                                <label for="city_id" class="control-label">
                                    Город

                                    <span class="form-element-required">*</span>
                                </label>
                                <div>
                                    <select id="city_id" size="2" data-select-type="single"
                                            data-url="{{route('dep_drop')}}"
                                            data-depends="[&quot;region_id&quot;]"
                                            class="form-control input-select input-select-dependent"
                                            name="city_id">
                                        <option value="">Выберите город</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Адрес</label>
                                <textarea class="form-control" type="text" name="address"></textarea>
                            </div>
                        </div>


                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Бюджет заказа (руб)</label>
                                <input class="form-control" type="number" name="sum" min="0" value="1">
                            </div>
                            <div class="form-group">
                                <label>Комментарий</label>
                                <textarea class="form-control" type="text" name="comment"></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Кол-во смен</label>
                                        <input class="form-control" type="number" name="days" min="1" value="1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group input-date">
                                        <label>Дата начала</label>
                                        <input class="form-control" type="text" name="date">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">Создать заявку!</button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <a class="btn btn-primary" target="_blank" href="{{route('order.show', $form->proposal->id)}}">Созданая
                    заявка #{{$form->proposal->id}}</a>
            @endif
        </div>
    </div>

</div>

<script>
    var token = document.head.querySelector('meta[name="csrf-token"]').content;
    document.addEventListener("DOMContentLoaded", function (event) {

        Admin.WYSIWYG.switchOn('letter', 'ckeditor', {
            'language': 'en',
            'removeButtons': 'Save',
            "height": 500,
            "script": true,
            "allowedContent": true,
            "extraPlugins":"panelbutton,uploadimage,image2,justify,youtube,uploadfile,colorbutton,a11yhelp,about,basicstyles,blockquote,clipboard,colorbutton,contextmenu,elementspath,enterkey,entities,filebrowser,floatingspace,font,format,horizontalrule,htmlwriter,image,indentlist,justify,link,list,magicline,maximize,pastefromword,pastetext,removeformat,resize,scayt,showborders,sourcearea,specialchar,stylescombo,tab,table,tableselection,tabletools,toolbar,undo,uploadimage,wsc,wysiwygarea",
            "uploadUrl": "https://office.transbaza.com/ckeditor/upload/image?_token=" + token,
            "filebrowserUploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token
        })
        $('[name=date]').datetimepicker({
            format: 'YYYY/MM/DD HH:mm',
            defaultDate: "{{\Carbon\Carbon::now()->addDay(1)->startOfDay()->addHours(8)}}",
        })
        $(document).on('submit', '#create_proposal', function (e) {
            e.preventDefault();
            let $form = $(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'PATCH',
                data: $form.serialize(),
                success: function (e) {
                    swal('Заявка создана');

                    setTimeout(function () {
                        location.reload();
                    }, 3000)
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }
            })
        })
    });
</script>