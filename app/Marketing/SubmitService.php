<?php

namespace App\Marketing;

use App\City;
use App\Machines\Type;
use App\Support\Region;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SubmitService extends Model
{
    protected $fillable = [
        'email', 'phone', 'comment', 'region_id', 'city_id',
        'type_id', 'proposal_id', 'user_id', 'address',
        'service_id', 'start_date', 'contractor_service_id', 'url'
    ];

    protected $dates = ['start_date'];


    function proposals()
    {
        return $this->belongsToMany(Proposal::class, 'submit_service_proposal');
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function category()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function service()
    {
        return $this->belongsTo(Service::class);
    }

    function contractor_service()
    {
        return $this->belongsTo(User\Contractor\ContractorService::class);
    }

}
