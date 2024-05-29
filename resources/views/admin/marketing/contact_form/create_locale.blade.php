<div class="content body">
    <div class="panel">
        <div class="panel-body">
            <h3>Локализация контактнуой формы</h3>
            @if(Session::has('errors'))
                <div class="alert alert-warning">
                    <strong>Внимание!</strong>{!! Session::get('errors') !!}
                </div>
            @endif
            <form class="row" method="POST" action="{{route('contact-form-locale.store')}}">
                @csrf
                <input type="hidden" name="locale" value="{{$locale}}">
                <input type="hidden" name="contact_id" value="{{$contact_id}}">
                <div class="col-md-12">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Наименование формы</label>
                            <input class="form-control" type="text" name="name" value="{{old('name')}}">
                        </div>

                        <div class="form-group">
                            <label>Текст лейбла "комменатрии"</label>
                            <input class="form-control" type="text" name="comment_label" value="{{old('comment_label')}}">
                        </div>
                        <div class="form-group">
                            <label>Текст кнопки</label>
                            <input class="form-control" type="text" name="button_text" value="{{old('button_text')}}">
                        </div>
                        <div class="form-group">
                            <label>Текст формы</label>
                            <textarea class="form-control" id="html_{{$locale}}" type="text" name="form_text">{{old('form_text')}}</textarea>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="form-group form-element-dependentselect">
                            <label class="control-label">Шаблон</label>
                            <select style="width:100%;" class="form-control input-select"
                                    data-placeholder="Выберите шаблон..." name="template_id">
                                @foreach($templates as $template)
                                    <option value="{{$template->id}}" {{old('template_id') === $template->id ? 'selected' : ''}}>{{$template->name}}</option>
                                @endforeach
                            </select>
                        </div>
                      {{--  <div class="form-group form-element-dependentselect">
                            <label class="control-label">Шаблон SMS</label>
                            <select style="width:100%;" class="form-control input-select"
                                    data-placeholder="Выберите шаблон..." name="phone_template_id">
                                @foreach($templates_phone as $template)
                                    <option value="{{$template->id}}" {{old('phone_template_id') === $template->id ? 'selected' : ''}}>({{$template->type === 'phone' ? 'TXT' : 'HTML'}}) {{$template->name}}</option>
                                @endforeach
                            </select>
                        </div>--}}
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button class="btn btn-primary" type="submit">Сохранить</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <hr>

        </div>
    </div>

</div>