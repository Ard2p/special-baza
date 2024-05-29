<table class="table table-striped table-bordered rotate_table">
    <thead>
    <tr>
        <th rowspan="2"><div>Статистика</div></th>
        <th rowspan="2"><div>Total</div></th>
        <th rowspan="2"><div>Total (Actual)</div></th>
        <th colspan="4"><div>Actual</div></th>
        <th colspan="4"><div>Blocked</div></th>
        <th colspan="4"><div>Deleted</div></th>

    </tr>
    <tr>
        <th ><div>ET</div></th>
        <th ><div>EnoT</div></th>
        <th ><div>TnoE</div></th>
        <th ><div>noET</div></th>

        <th><div>ET</div></th>
        <th><div>EnoT</div></th>
        <th><div>TnoE</div></th>
        <th><div>noET</div></th>

        <th><div>ET</div></th>
        <th><div>EnoT</div></th>
        <th><div>TnoE</div></th>
        <th><div>noET</div></th>
    </tr>
    </thead>
    <tbody>

    @foreach(\App\Role::all() as $role)
        <tr data-role="{{$role->alias}}">
            <td>{{$role->name}}</td>
            <td class="user_info" data-type="all_1">{{$role->users()->withTrashed()->count()}}</td>
            <td class="user_info" data-type="actual_1">{{$role->users()->whereIsBlocked(0)->count()}}</td>


            <td class="user_info" data-type="active_ET">{{$role->users()->whereIsBlocked(0)->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
            <td class="user_info" data-type="active_EnoT">{{$role->users()->whereIsBlocked(0)->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
            <td class="user_info" data-type="active_TnoE">{{$role->users()->whereIsBlocked(0)->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
            <td class="user_info" data-type="active_noET">{{$role->users()->whereIsBlocked(0)->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

            <td class="user_info" data-type="blocked_ET">{{$role->users()->whereIsBlocked(1)->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
            <td class="user_info" data-type="blocked_EnoT">{{$role->users()->whereIsBlocked(1)->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
            <td class="user_info" data-type="blocked_TnoE">{{$role->users()->whereIsBlocked(1)->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
            <td class="user_info" data-type="blocked_noET">{{$role->users()->whereIsBlocked(1)->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

            <td class="user_info" data-type="trashed_ET">{{$role->users()->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
            <td class="user_info" data-type="trashed_EnoT">{{$role->users()->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
            <td class="user_info" data-type="trashed_TnoE">{{$role->users()->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
            <td class="user_info" data-type="trashed_noET">{{$role->users()->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

          {{--  <td class="user_info" data-type="not_confirm">{{$role->users()->whereIsBlocked(0)->whereEmailConfirm(0)->count()}}</td>
            <td class="user_info" data-type="blocked">{{$role->users()->whereIsBlocked(1)->count()}}</td>
            <td class="user_info" data-type="deleted">{{$role->users()->withTrashed()->whereNotNull('deleted_at')->count()}}</td>--}}
        </tr>
    @endforeach
    <tr data-role="regional">
        <td>РП</td>
        <td class="user_info" data-type="all_1">{{\App\User::whereIsRegionalRepresentative(1)->withTrashed()->count()}}</td>
        <td class="user_info" data-type="actual_1">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(0)->count()}}</td>

        <td class="user_info" data-type="active_ET">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(0)->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="active_EnoT">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(0)->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
        <td class="user_info" data-type="active_TnoE">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(0)->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="active_noET">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(0)->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

        <td class="user_info" data-type="blocked_ET">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(1)->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="blocked_EnoT">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(1)->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
        <td class="user_info" data-type="blocked_TnoE">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(1)->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="blocked_noET">{{\App\User::whereIsRegionalRepresentative(1)->whereIsBlocked(1)->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

        <td class="user_info" data-type="trashed_ET">{{\App\User::whereIsRegionalRepresentative(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="trashed_EnoT">{{\App\User::whereIsRegionalRepresentative(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
        <td class="user_info" data-type="trashed_TnoE">{{\App\User::whereIsRegionalRepresentative(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="trashed_noET">{{\App\User::whereIsRegionalRepresentative(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>


    </tr>
    <tr data-role="promoter">
        <td>Промоутер</td>
        <td class="user_info" data-type="all_1">{{\App\User::whereIsPromoter(1)->withTrashed()->count()}}</td>
        <td class="user_info" data-type="actual_1">{{\App\User::whereIsPromoter(1)->whereIsBlocked(0)->count()}}</td>
        <td class="user_info" data-type="active_ET">{{\App\User::whereIsPromoter(1)->whereIsBlocked(0)->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="active_EnoT">{{\App\User::whereIsPromoter(1)->whereIsBlocked(0)->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
        <td class="user_info" data-type="active_TnoE">{{\App\User::whereIsPromoter(1)->whereIsBlocked(0)->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="active_noET">{{\App\User::whereIsPromoter(1)->whereIsBlocked(0)->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

        <td class="user_info" data-type="blocked_ET">{{\App\User::whereIsPromoter(1)->whereIsBlocked(1)->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="blocked_EnoT">{{\App\User::whereIsPromoter(1)->whereIsBlocked(1)->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
        <td class="user_info" data-type="blocked_TnoE">{{\App\User::whereIsPromoter(1)->whereIsBlocked(1)->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="blocked_noET">{{\App\User::whereIsPromoter(1)->whereIsBlocked(1)->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

        <td class="user_info" data-type="trashed_ET">{{\App\User::whereIsPromoter(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(1)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="trashed_EnoT">{{\App\User::whereIsPromoter(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(1)->wherePhoneConfirm(0)->count()}}</td>
        <td class="user_info" data-type="trashed_TnoE">{{\App\User::whereIsPromoter(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(0)->wherePhoneConfirm(1)->count()}}</td>
        <td class="user_info" data-type="trashed_noET">{{\App\User::whereIsPromoter(1)->withTrashed()->whereNotNull('deleted_at')->whereEmailConfirm(0)->wherePhoneConfirm(0)->count()}}</td>

    </tr>
    <tr>
        <td   class="user_info" data-role="total"><b>Всего пользователей:</b> {{\App\User::withTrashed()->count()}}</td>
        <td colspan="4" class="user_info" data-role="active"><b>Действующие</b> {{\App\User::whereEmailConfirm(1)->whereIsBlocked(0)->count()}}</td>
    </tr>
{{--    <td class="user_info" data-role="{{$role->alias}}">
        {{\App\User::whereHas('roles', function ($q) use($role){
              $q->whereAlias($role->alias);
        })->count()}}
    </td>
    <td class="user_info" data-role="regional">{{\App\User::whereIsRegionalRepresentative(1)->count()}}</td>
    <td class="user_info" data-role="promoter">{{\App\User::whereIsPromoter(1)->count()}}</td>
    <tr>
        <td colspan="6"></td>
        <td class="user_info" data-role="total"><b>Всего пользователей:</b> {{\App\User::all()->count()}}</td>
        <td class="user_info" data-role="confirm"><b>Подвержденный
                Email:</b> {{\App\User::whereEmailConfirm(1)->count()}}</td>
    </tr>--}}

    </tbody>
</table>