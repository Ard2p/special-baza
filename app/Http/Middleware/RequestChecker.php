<?php

namespace App\Http\Middleware;

use App\Ads\AdvertBlackList;
use App\Marketing\EmailLink;
use App\Marketing\SendingMails;
use App\Marketing\SendingSms;
use App\Marketing\ShareList;
use App\Marketing\SmsLink;
use App\User\SendingSubscribe;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;

class RequestChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->filled('dis_id')) {
            $share = ShareList::whereConfirmStatus(0)->find($request->dis_id);
            if ($share) {
                $share->update([
                    'confirm_status' => 2,
                    'confirm_at' => Carbon::now()
                ]);
            }

        }
        if ($request->filled('dis_share_email_id')) {
            $share = EmailLink::whereConfirmStatus(0)->find($request->dis_share_email_id);
            if ($share) {
                if($share->advert){
                    AdvertBlackList::create([
                        'email' => $share->friend->email,
                        'advert_id' => $share->advert->id,
                    ]);
                }
                $share->update([
                    'confirm_status' => 2,
                    'confirm_at' => Carbon::now()
                ]);
            }

        }
        if ($request->filled('form_sending_id')) {
            $share = SendingMails::whereConfirmStatus(0)
                ->whereHash($request->hash ?: 0)
                ->find($request->form_sending_id);
            if ($share) {
                $share->update([
                    'confirm_status' => 1,
                    'confirm_at' => Carbon::now()
                ]);
            }

        }
        if ($request->filled('dis_form_sending_id')) {
            $share = SendingMails::whereConfirmStatus(0)
                ->whereHash($request->hash ?: 0)
                ->find($request->dis_form_sending_id);
            if ($share) {
                $share->update([
                    'confirm_status' => 2,
                    'confirm_at' => Carbon::now()
                ]);
            }

        }
        if ($request->filled('click_friend_id')) {
            $share = EmailLink::with('friend')
                ->whereHash($request->hash ?: 0)
                ->whereConfirmStatus(0)->find($request->click_friend_id);
            if ($share) {
                DB::beginTransaction();
                $share->update([
                    'confirm_status' => 1,
                    'confirm_at' => Carbon::now()
                ]);

                $share->friend->user->incrementTbcBalance(100, $share);
                DB::commit();
            }

        }
        if ($request->filled('click_friend_sms_id')) {
            $share = SmsLink::whereConfirmStatus(0)
                ->whereHash($request->hash ?: 0)
                ->find($request->click_friend_sms_id);
            if ($share) {
                 DB::beginTransaction();
                $share->update([
                    'confirm_status' => 1,
                    'confirm_at' => Carbon::now()
                ]);
                $share->friend->user->incrementTbcBalance(100, $share);
                DB::commit();
            }

        }

        if ($request->filled('subscribe_sending_id')) {
            $share = SendingSubscribe::whereConfirmStatus(0)
                ->whereHash($request->hash ?: 0)
                ->find($request->subscribe_sending_id);
            if ($share) {
                DB::beginTransaction();
                $share->update([
                    'confirm_status' => 1,
                    'confirm_at' => Carbon::now()
                ]);
                DB::commit();
            }

        }
        if ($request->filled('dis_subscribe_sending_id')) {
            $share = SendingSubscribe::whereConfirmStatus(0)
                ->whereHash($request->hash ?: 0)
                ->find($request->dis_subscribe_sending_id);
            if ($share) {
                DB::beginTransaction();
                $share->update([
                    'confirm_status' => 2,
                    'confirm_at' => Carbon::now()
                ]);
                DB::commit();
            }

        }

        if ($request->filled('fsk_sending_id')) {
            $share = SendingSms::whereConfirmStatus(0)
                ->whereHash($request->hash ?: 0)
                ->find($request->fsk_sending_id);
            if ($share) {
                DB::beginTransaction();
                $share->update([
                    'confirm_status' => 1,
                    'confirm_at' => Carbon::now()
                ]);
                DB::commit();
            }

        }
        if ($request->filled('dis_fsk_sending_id')) {
            $share = SendingSms::whereConfirmStatus(0)
                ->whereHash($request->hash ?: 0)
                ->find($request->dis_fsk_sending_id);
            if ($share) {
                DB::beginTransaction();
                $share->update([
                    'confirm_status' => 2,
                    'confirm_at' => Carbon::now()
                ]);
                DB::commit();
            }

        }
        return $next($request);
    }
}
