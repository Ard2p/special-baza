<?php

namespace App\Http\Controllers\Avito\Models;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class AvitoLog extends Model
{
    protected $fillable = [
        'avito_order_id',
        'avito_request_status',
        'request_url',
        'request_body',
        'response',
        'status',
        'error_message'
    ];

    protected $casts =[
        'request_body' => 'array',
        'response' => 'array',
    ];
    public function avito_order()
    {
        return $this->belongsTo(AvitoOrder::class);
    }
}
