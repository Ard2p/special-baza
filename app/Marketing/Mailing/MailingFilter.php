<?php

namespace App\Marketing\Mailing;

use Illuminate\Database\Eloquent\Model;

class MailingFilter extends Model
{
    protected $table = 'mailing_search_filters';

    protected $fillable = [
        'name',
        'fields',
        'type',
    ];

    protected $appends = [
        'show_link',
        'delete_link',
    ];

    static $needleFields = [
        "list_id",
        "role_id",
        "type_id",
        "region_id",
        "city_id",
        "email_confirm",
        "phone_confirm",
    ];


    function getShowLinkAttribute()
    {
        return route('filters.edit', $this->id);
    }

    function getArrayAttribute()
    {
        return json_decode($this->fields, true);
    }

    function getDeleteLinkAttribute()
    {
        return route('filters.destroy', $this->id);
    }

}
