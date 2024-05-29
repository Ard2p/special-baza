<div class="form-group form-element">
    <label class="control-label">
    Полное удаление пользователя
    </label>
    <br>
        <button class="btn btn-danger" data-url="{{route('total_delete', $user->id)}}" type="button" id="delete_user">Удалить пользователя</button>
    <hr>
</div>
<ul class="list-group">
    <li class="list-group-item">Есть реальные транзакции {!!  $user->hasRealBalance() ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>' !!}</li>
    <li class="list-group-item">Действующий пользователь {!! (!$user->is_blocked && $user->email_confirm && !$user->deleted_at) ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>'!!}</li>
    <li class="list-group-item">Подтвержденный email {!! $user->email_confirm ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>'!!}</li>
    <li class="list-group-item">Заблокированный {!! $user->is_blocked ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>'!!}</li>
    <li class="list-group-item">Удаленный {!! $user->deleted_at ? '<i class="fa fa-check"></i>' : '<i class="fa fa-minus"></i>'!!}</li>
</ul>

