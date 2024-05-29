<?php

namespace App\Ads;

use App\Article;
use App\City;
use App\Marketing\EmailLink;
use App\Marketing\SmsLink;
use App\Offer;
use App\Support\Region;
use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Advert extends Model
{
    protected $fillable = [
        'category_id', 'status', 'sum', 'region_id',
        'city_id', 'address', 'photo', 'coordinates',
        'actual_date', 'user_id', 'reward_id', 'reward_text', 'description',
        'name', 'alias', 'views', 'guest_views', 'global_show'
    ];

    static $status = [
        'open',
        'done',
        'close'
    ];

    static $status_lng = [
        'Открыто',
        'Есть исполнитель',
        'В архиве'
    ];

    protected $dates = ['actual_date'];

    protected static function boot()
    {
        parent::boot();

        self::created(function ($item) {
            $token = $item->id . '-' . str_random(8);

            $item->update([
                'alias' => $token
            ]);
        });

        self::deleted(function (Advert $item) {
           $item->user_views()->detach();
           $item->sendingSms()->delete();
           $item->sendingEmails()->delete();
           $item->agents()->detach();
           $item->offers()->delete();
        });
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function getStatusNameAttribute()
    {
        return self::$status_lng[$this->status];
    }

    function user_views()
    {
        return $this->belongsToMany(User::class, 'advert_view_user');
    }

    /*advert_send_sms
    advert_send_email*/

    function sendingSms()
    {
        return $this->belongsToMany(SmsLink::class, 'advert_send_sms');
    }

    function sendingEmails()
    {
        return $this->belongsToMany(EmailLink::class, 'advert_send_email');
    }

    function setSumAttribute($value)
    {
        $this->attributes['sum'] = round(str_replace(',', '.', $value) * 100);
    }

    function getSumFormatAttribute()
    {
        return number_format($this->sum / 100, 0, ',', ' ');
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function category()
    {
        return $this->belongsTo(AdvertCategory::class, 'category_id');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function offers()
    {
        return $this->hasMany(AdvertOffer::class);
    }

    function agents()
    {
        return $this->belongsToMany(User::class, 'advert_agents')->withPivot('parent_id');
    }

    function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    function scopeCurrentUser($q)
    {
        return $q->whereUserId(Auth::id());
    }

    function scopeCurrentWinner($q)
    {
        return $q->whereHas('offers', function ($q) {
            $q->where('user_id', Auth::id());
        });
    }

    function scopeNoFeedback($q)
    {
        return $q->whereHas('offers', function ($q) {
            $q->where('rate', 0);
        });
    }

    function scopeIsAgent($q)
    {
        return $q->where(function ($q){
           $q->whereHas('agents', function ($q){
               return $q->where('users.id', Auth::id());
           })->orWhere('user_id', Auth::id());
        });
    }

    function getUrlAttribute()
    {
        return route('adverts', $this->alias);
    }

    function getFullAddressAttribute()
    {
        return "{$this->region->name}, {$this->city->name}, {$this->address}";
    }

    function getInfoLink()
    {
        $q = request()->query();
        $q['get_info'] = 1;
        return request()->url() . '?' . http_build_query($q);
    }

    function setMeContractor()
    {
        $q = request()->query();
        $q['set_contractor'] = 1;
        return request()->url() . '?' . http_build_query($q);
    }

    function setMeAgent()
    {
        $q = request()->query();
        $q['set_agent'] = 1;
        return request()->url() . '?' . http_build_query($q);
    }

    function isAgent(User $user = null)
    {
        $user = $user ?: Auth::user();
        return $this->agents()->where('users.id', $user->id)->first() || $this->user_id === $user->id;
    }

    function scopeImAgent($q, $user = null)
    {
        return $q->whereHas('agents', function ($q) use ($user) {
            $q->where('users.id', $user ? $user->id : Auth::id());
        });
    }

    function scopeImContractor($q, $user_id = null)
    {
        return $q->whereHas('offers', function ($q) use ($user_id) {
            $q->where('user_id', $user_id ?: Auth::id());
        });
    }

    function scopeGlobalShow($q, $show = true)
    {
        return $q->where('global_show', true);
    }


    function buildAgents($user_id)
    {
        static $users;


        if ($user_id === $this->user->id) {
           $users[] = $this->user;

            return array_reverse($users);
        }
        $agent = $this->agents()->where('users.id', $user_id)->firstOrFail();

        $user = User::findOrFail($user_id);
        $users[] = $user;

        return $this->buildAgents($agent->pivot->parent_id);
    }

    function scopeCheckAvailable($q)
    {
        return $q->whereStatus(0);
    }

    function scopeCheckAccepted($q)
    {
        return $q->whereStatus(1);
    }


    function hasOffer($id)
    {
        return $this->offers()->where('user_id', $id)->first();
    }

    function isActive()
    {
        return $this->status === 0;
    }

    function isComplete()
    {
        return $this->status === 1;
    }

    function winner()
    {
        return $this->hasOne(AdvertOffer::class)->where('advert_offers.is_win', '=', 1);
    }

    function isCustomer()
    {
        return $this->user_id === Auth::id();
    }

    function isContractor()
    {
        return $this->winner->user_id === Auth::id();
    }

    function hasFeedback()
    {
        return $this->winner->rate !== 0;
    }

    function getRefererLink($hash = null)
    {
        if (Auth::check()) {
            if ($hash) {
                $link = EmailLink::whereHash($hash)->forAdvert($this->id)->first();
                if (!$link) {
                    $link = SmsLink::whereHash($hash)->forAdvert($this->id)->first();
                }
            } else {
                $link = EmailLink::whereHas('friend', function ($q) {
                    $q->whereEmail(Auth::user()->email);
                })->forAdvert($this->id)->first();

                if (!$link) {
                    $link = SmsLink::whereHas('friend', function ($q) {
                        $q->wherePhone(Auth::user()->phone);
                    })->forAdvert($this->id)->first();
                }
            }


        } else {
            $link = EmailLink::whereHash(request()->hash)->forAdvert($this->id)->first();
            if (!$link) {
                $link = SmsLink::whereHash(request()->hash)->forAdvert($this->id)->first();
            }

        }
        return $link;
    }

    function getAgentsCount()
    {
        $link = $this->getRefererLink(request()->input('hash'));
        return $link ? count($this->buildAgents($link->advert->pivot->user_id), true)
            : 1;
    }

    function getOpenMailsAttribute()
    {
        return $this->sendingEmails()->whereIsWatch(1)->get()->count();
    }


    function getAcceptMailsAttribute()
    {
        return $this->sendingEmails()->whereConfirmStatus(1)->get()->count();
    }

    function getSumViewsAttribute()
    {
        return $this->views + $this->guest_views;
    }



}
