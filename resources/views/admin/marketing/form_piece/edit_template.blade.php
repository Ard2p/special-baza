<form id="edit_template_form" action="{{route('templates.update', $template->id)}}">
    @csrf
    @method('PATCH')
    <div class="form-group">
        <label>Наименование шаблона</label>
        <input class="form-control" type="text" name="name" value="{{$template->name}}">
    </div>
    <input type="hidden" name="type" value="{{$template->type}}">

    <div class="form-group">
        <label>Текст</label>
        <textarea rows="10" class="form-control" id="editable" type="text" name="text">{{$template->text}}</textarea>
    </div>
</form>