<?php

namespace App\Marketing;

use App\Ads\AdvertBlackList;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FriendsList extends Model
{

    use SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'user_id',
    ];
    protected $appends = [
        'sms_links_count',
        'email_links_count',
        'info_link',
        'friend_link',
        'delete_link',
        'update_link',
        'email_adverts_count',
        'sms_adverts_count',
        'friend_relation_type',
    ];

   function sms_links()
   {
       return $this->hasMany(SmsLink::class);
   }

    function email_links()
    {
        return $this->hasMany(EmailLink::class);
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function phoneFriend()
    {
        return $this->hasOne(User::class, 'phone', 'phone');
    }

    function emailFriend()
    {
        return $this->hasOne(User::class, 'email', 'email');
    }

    function getFriendLinkAttribute()
    {
        if($this->phone){
            return $this->phoneFriend
                ? '<a target="_blank" href="' . route('contractor_public_page', $this->phoneFriend->contractor_alias) . '">#' .$this->phoneFriend->id .  '</a>'
                : null;
        }
        if($this->email){
            return $this->emailFriend
                ? '<a target="_blank" href="' . route('contractor_public_page', $this->emailFriend->contractor_alias) . '">#' .$this->emailFriend->id .  '</a>'
                : null;
        }
        return 'Не найден.';
    }

    function getFriendRelationTypeAttribute()
    {
         if($this->phone && $this->phoneFriend){
             return self::whereUserId($this->phoneFriend->id)->wherePhone($this->user->phone)->first() ? '<i class="fa  fa-arrow-left"></i><i class="fa  fa-arrow-right"></i>' : '<i class="fa  fa-arrow-right"></i>';
         }
        if($this->email && $this->emailFriend){
            return self::whereUserId($this->emailFriend->id)->whereEmail($this->user->email)->first() ? '<i class="fa  fa-arrow-left"></i><i class="fa  fa-arrow-right"></i>' : '<i class="fa  fa-arrow-right"></i>';
        }

        return 'Пригласите пользователя в систему.';
    }

    function getSmsLinksCountAttribute()
    {
        return $this->sms_links()->whereConfirmStatus(1)->count();
    }

    function getEmailLinksCountAttribute()
    {
        return $this->email_links()->whereConfirmStatus(1)->count();
    }


    function getInfoLinkAttribute()
    {
        return route('friends.show', $this->id);
    }

    function getPhoneFormatAttribute()
    {
        $phone = $this->phone;
        return "+{$phone[0]} ({$phone[1]}{$phone[2]}{$phone[3]}) {$phone[4]}{$phone[5]}{$phone[6]}-{$phone[7]}{$phone[8]}-{$phone[9]}{$phone[10]}";
    }

    function getDeleteLinkAttribute()
    {
        return route('friends.destroy', $this->id);
    }

    function getUpdateLinkAttribute()
    {
        return route('friends.update', $this->id);
    }

    function blackListEmail()
    {
        return $this->hasMany(AdvertBlackList::class, 'email', 'email');
    }
    function blackListPhone()
    {
        return $this->hasMany(AdvertBlackList::class, 'phone', 'phone');
    }

    function scopeNotInBlackListEmail($q, $advert_id)
    {
        return $q->whereDoesntHave('blackListEmail', function ($q) use($advert_id){
            $q->where('advert_black_lists.advert_id', $advert_id);
        });
    }
    function scopeNotInBlackListPhone($q, $advert_id)
    {
       return $q->whereDoesntHave('blackListPhone', function ($q) use($advert_id){
          $q->where('advert_black_lists.advert_id', $advert_id);
       });
    }
    function getEmailAdvertsCountAttribute()
    {
        return $this->email_links->count();
    }

    function getSmsAdvertsCountAttribute()
    {
        return $this->sms_links->count();
    }
}
