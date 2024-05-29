<?php

namespace App\Service;

use App\Marketing\EmailLink;
use App\Marketing\Mailing\ListName;
use App\Marketing\SmsLink;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Mailing
{

    static function getFilteredUsers(Request $request)
    {
        $users = User::query();

        if ($request->filled('type_id') && $request->type_id !== '0') {
            $users->whereHas('machines', function ($q) use ($request) {
                $q->whereIn('type', $request->type_id);
            });
        }
        if ($request->filled('list_id') && $request->list_id !== '0') {
            $lists = ListName::whereIn('id', $request->list_id);
            $ids = [];
            foreach ($lists as $list){
                if ($list->type === 'phone') {
                    $ids = array_merge($list->phones()->pluck('user_id'), $ids);
                } else {
                    $ids = array_merge($list->emails()->pluck('user_id'), $ids);
                }
            }


            $users->whereIn('id', $ids);
        }
        if ($request->filled('region_id') && $request->region_id !== '0') {
            $users->whereNativeRegionId($request->region_id);
        }
        if ($request->filled('email_confirm')) {

            $users->whereEmailConfirm(1);
        }
        if ($request->filled('phone_confirm')) {
            $users->wherePhoneConfirm(1);
        }
        if ($request->filled('role_id') && $request->role_id !== '0') {
            $users->whereHas('roles', function ($q) use ($request) {
                $q->whereIn('roles.id', $request->role_id);
            });
        }
        if ($request->filled('city_id') && $request->city_id !== '0') {
            $users->whereNativeCityId($request->city_id);
        }
        $users = $users->get();
        return $users;
    }


}