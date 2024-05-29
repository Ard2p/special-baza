<form action="{!! route('equipment.destroy', $id) !!}" method="POST" style="display:inline-block;">
    @csrf
    <input type="hidden" name="_method" value="delete">
    <button class="btn btn-xs btn-danger btn-delete" title="Удалить" data-toggle="tooltip">
        <i class="fa fa-trash"></i>

    </button>
</form>