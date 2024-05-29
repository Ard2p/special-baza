<?php

namespace Modules\Telephony\Entities;

use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\File;

class Call extends Model
{
    protected $fillable = [
        'phone',
        'call_id',
        'call_status',
        'global_status',
        'answered',
        'grabbed',
        'record_name',
        'type',
        'caller',
        'called',
    ];

    protected $appends = ['html_status', 'html_global_status', 'user_url'];

    function getHtmlStatusAttribute()
    {
        return view('telephony::html_status', ['call' => $this])->render();
    }

    function getHtmlGlobalStatusAttribute()
    {
        return view('telephony::html_global_status', ['call' => $this])->render();
    }

    function user()
    {
        return $this->belongsTo(User::class, 'phone', 'phone');
    }

    function getUserUrlAttribute()
    {

        return $this->user ? "https://office.trans-baza.ru/users/{$this->user->id}/edit" : null;
    }

    function getYandexData()
    {
        $api = new YandexAPI();
        return $api->getCall($this->call_id);
    }


    function updateYandexData()
    {
        $data = $this->getYandexData();
        $data = $data['data'] ?? [];

        if(!$data) {
            return;
        }

        if((!empty($data['callRecord'])) && isset($data['callRecord']['uri'])){
             $content = @file_get_contents($data['callRecord']['uri']);
             if(!$content) {
                 goto end;
             }
             $name = uniqid() .  "_{$this->id}.wav";
             File::put(storage_path("calls/{$name}"), $content);
        }
        end:
        $this->update([
            'record_name' => $name ?? '',
            'grabbed' => true,
        ]);
    }
}
