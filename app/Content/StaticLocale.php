<?php

namespace App\Content;

use App\Overrides\Model;

class StaticLocale extends Model
{
    protected $fillable = [
        'title', 'keywords', 'description',
        'h1', 'image_alt', 'content',
        'locale', 'user_id', 'static_content_id',
    ];

    function article()
    {
        return $this->belongsTo(StaticContent::class, 'static_content_id');
    }
}
