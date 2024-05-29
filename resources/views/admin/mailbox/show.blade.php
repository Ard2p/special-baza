<div class="panel">
    <ol class="breadcrumb">
        <li><a href="{{route('mail-box.index')}}">Почта</a></li>
        <li class="active">{{$mail->subject}}</li>
    </ol>
    <div class="panel-body">
        <h2>{{$mail->subject}}</h2>
        <p>От: {{$mail->fromAddress}}</p>
        <div>
            {!!   $mail->textHtml !!}
        </div>
        @if(!$user)
         <form action="{{route('register_user_from_mail')}}" id="registerUser">
             @csrf
             <div class="col-md-6">
                 <h3>Пользователь не найден. Зарегистрировать пользователя?</h3>
                 <input type="hidden" value="{{$mail->fromAddress}}" name="email">
                 <div class="form-group">
                     <label>EMAIL</label>
                     <input class="form-control" type="text" value="{{$mail->fromAddress}}" disabled>
                 </div>
                 <div class="form-group">
                     <label>Телефон</label>
                     <input class="form-control phone-format" type="text" name="phone">
                 </div>
                 <div class="form-group">
                     <div class="btn-group">
                         <button type="submit" class="btn btn-success">Зарегистрировать!</button>
                     </div>
                 </div>
             </div>
         </form>
        @endif
        <form class="row" method="POST" id="create_proposal"
              action="">
            @csrf
            @method('PATCH')
            <input type="hidden" name="user_id" value="{{$user->id ?? 0}}">
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
    </div>

</div>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        $(document).on('submit', '#registerUser', function (e) {
            e.preventDefault();
            let $form = $(this)
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success:function (e) {
                    swal(e.message)
                    setTimeout(function () {
                        location.reload()
                    }, 2500)
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }
            })
        })
        $('.phone-format').mask('+7 (000) 000-00-00');
    });
</script>
