 <div class="panel">
        <div class="panel-body">
            <h3>Локализация формы простой заявки</h3>
            @if(Session::has('errors'))
                <div class="alert alert-warning">
                    <strong>Внимание!</strong>{!! Session::get('errors') !!}
                </div>
            @endif
            <form class="row" method="POST" action="{{route('simple-proposal-locale.store')}}">
                @csrf
                <input type="hidden" name="locale" value="{{$locale}}">
                <input type="hidden" name="simple_proposal_id" value="{{$simple_id}}">
                <div class="col-md-12">
                    <div class="col-md-6">

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