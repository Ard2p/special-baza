<?php

namespace App\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrincipalDoc extends \App\Overrides\Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'number',
        'start_date',
        'end_date',
        'scans',
        'individual_requisite_id',
        'is_rent',
        'is_service',
        'is_part_sale',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'scans' => 'array',
        'is_rent' => 'boolean',
        'is_service' => 'boolean',
        'is_part_sale' => 'boolean',
    ];

    function person()
    {
        return $this->belongsTo(IndividualRequisite::class, 'individual_requisite_id');
    }

}
