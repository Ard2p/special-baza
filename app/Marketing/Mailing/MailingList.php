<?php

namespace App\Marketing\Mailing;

use Illuminate\Database\Eloquent\Model;

class MailingList extends Model
{

    protected $fillable = [
        'name',
        'type',
        'template_id',
        'status',
        'subject',
        'fields',
    ];


    function template()
    {
        return $this->belongsTo(Template::class);
    }


    function lists()
    {
        return $this->belongsToMany(ListName::class, 'list_name_mailing');
    }

    function filters()
    {
        return $this->belongsToMany(MailingFilter::class, 'mailing_filters');
    }

    function getStatusAttribute($val)
    {
        return $val ? 'Завершено' : 'В процессе';
    }
}
