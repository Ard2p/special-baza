<div class="content body" id="tables_content">
    <div class="panel">
        <div class="panel-body">
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
                        @if($form->ticket)
                            <div class="form-group input-group">
                                <label>Тикет в технической поддержке</label>
                                <input class="form-control" type="text" value="{{$form->ticket->category->name ?? ''}}" disabled>
                                <div class="input-group-btn">

                                    <a target="_blank" class="btn btn-default" href="/tickets/{{$form->ticket->id}}/edit" style="    margin-top: 25px;">Перейти</a>
                                </div>
                            </div>
                            @endif
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
                                <button class="btn btn-info" type="button">Написать письмо</button>

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

        </div>
    </div>

</div>