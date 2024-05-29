<?php

namespace App\Support;

use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'files', 'message', 'is_admin', 'is_read'
    ];
    public static $requiredFields = [
        'message' => 'required|string',
        'ticket_id' => 'required|integer',

    ];

    function scopeSupportMessages($q)
    {
        return $q->where('is_admin', 1);
    }
    function scopeUserMessages($q)
    {
        return $q->where('is_admin', 0);
    }

    function makeFiles($files, bool $update = false)
    {
        $scans = ($update) ? json_decode($this->scans, true) : [];
        foreach ($files as $file) {

            $extension = $file->getClientOriginalExtension();
            $fileName = str_random(5) . "-" . date('his') . "-" . str_random(3) . "." . $extension;
            $folderpath = public_path('images');
            $file->move($folderpath, $fileName);
            Image::make($folderpath . '/' . $fileName)->save($folderpath . '/' . $fileName, 50);
            $scans[] = 'images/' . $fileName;
        }
        $this->files = json_encode($scans);
        return $this;
    }

    public function user()
    {
        return $this->hasOne(\App\User::class, 'id', 'user_id')->withTrashed();;
    }
}
