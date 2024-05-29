<?php

namespace App\Marketing;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ShareList extends Model
{
    protected $fillable = [
        'url',
        'email',
        'phone',
        'type',
        'is_watch',
        'watch_at',
        'confirm_status',
        'confirm_at',
    ];
   static function renderShare()
   {
       if(request()->filled('click_id')){
           $share = self::whereConfirmStatus(0)->find(request('click_id'));
           if($share && $share->confirm_status === 0) {
               $share->update([
                   'confirm_status' => 1,
                   'confirm_at' => Carbon::now()
               ]);
           }

       }

       return;//view('marketing.share')->render();
   }

   function getPhoneFormatAttribute()
   {
       $phone = $this->phone;
       return "+{$phone[0]} ({$phone[1]}{$phone[2]}{$phone[3]}) {$phone[4]}{$phone[5]}{$phone[6]}-{$phone[7]}{$phone[8]}-{$phone[9]}{$phone[10]}";
   }

   function sendSms($number, $text)
   {

   }

}
