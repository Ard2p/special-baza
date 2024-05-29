<?php

namespace App\Directories;

use App\User\Contractor\ContractorService;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    protected $fillable = [
        'name', 'name_style', 'alias'
    ];

    function contractor_services()
    {
        return $this->hasMany(ContractorService::class);
    }
}
