<?php

namespace App\Marketing\Mailing;

use App\Marketing\ContactForm;
use Illuminate\Database\Eloquent\Model;

class ListName extends Model
{

    protected $fillable = [
        'name',
        'type',
        'contact_form_id',
    ];

    protected $appends = [
      'show_link',
      'delete_link',
    ];
    function emails()
    {
        return $this->belongsToMany(EmailList::class, 'list_name_email')->withTimestamps();
    }

    function phones()
    {
        return $this->belongsToMany(PhoneList::class, 'list_name_phone')->withTimestamps();
    }

    function getShowLinkAttribute()
    {
        return route('mailing_list.edit', $this->id);
    }

    function getDeleteLinkAttribute()
    {
        return route('mailing_list.destroy', $this->id);
    }

    function contact_form()
    {
        return $this->belongsTo(ContactForm::class);
    }


}
