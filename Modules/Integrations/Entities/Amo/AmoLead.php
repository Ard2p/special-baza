<?php

namespace Modules\Integrations\Entities\Amo;

use Illuminate\Database\Eloquent\Model;
use Modules\Dispatcher\Entities\Lead;

class AmoLead extends Model
{
    protected $fillable = [
        'amo_id',
        'lead_id',
        'data',
        'status',
    ];

    protected $casts = [
        'data' => 'object'
    ];

    const STATUS_PROCESSED = 'processed';
    const STATUS_UNPROCESSED = 'unprocessed';

    function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
