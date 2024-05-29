<?php

namespace App\Support;

use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class Ticket extends Model
{

    protected $fillable = ['title', 'user_id', 'status', 'category_id', 'submit_ticket_popup_id'];

    public static  $requiredFields = [
        'title'  => 'required|string|max:255',
        'message'  => 'required|string',
        'category'  => 'required|integer',
        'files.*' => 'nullable|sometimes|string'
    ];

    const PROP_STATUS = [
        'open',
        'close',
    ];
    const PROP_STATUS_LNG = [
        'открыт',
        'закрыт',
    ];

    static function status($key)
    {
        return array_search($key, self::PROP_STATUS);
    }

    static function status_lng($key)
    {
        return self::PROP_STATUS_LNG[$key];
    }

    function scopeCurrentUser($q)
    {
        return $q->where('user_id', Auth::user()->id);
    }

    function scopeIsOpen($q)
    {
        return $q->where('status', $this->status('open'));
    }

    function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id')->withTrashed();
    }

    function category()
    {
        return $this->hasOne('App\Support\SupportCategory', 'id', 'category_id');
    }

    function messages()
    {
        return $this->hasMany('App\Support\TicketMessage', 'ticket_id', 'id');
    }

    function unread_user_messages()
    {
        return $this->hasMany('App\Support\TicketMessage', 'ticket_id', 'id')
            ->where('ticket_messages.is_admin', '=', 0)
            ->where('ticket_messages.is_read', '=', 0);
    }

    function scopeUnread($q){
        return $q->withCount('unread_user_messages')
        ->orderBy('unread_user_messages_count', 'DESC')
        ->orderBy('created_at', 'DESC');
    }


}
