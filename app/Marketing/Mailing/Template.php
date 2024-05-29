<?php

namespace App\Marketing\Mailing;

use Illuminate\Database\Eloquent\Model;
use Modules\RestApi\Entities\Domain;

class Template extends Model
{

/*    protected $table = 'mailing_templates';

    protected $fillable = [
        'name',
        'text',
        'domain_id',
        'type'
    ];

    protected $appends = [
        'update_link', 'type_name'
    ];

    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    function getUpdateLinkAttribute()
    {
        return route('templates.show', $this->id);
    }


    function getTypeNameAttribute()
    {
        return $this->type === 'phone' ? 'Телефоны' : 'Email';
    }*/
}
