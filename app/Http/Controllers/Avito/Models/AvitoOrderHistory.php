<?php

namespace App\Http\Controllers\Avito\Models;

use App\Overrides\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class AvitoOrderHistory extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'avito_order_id',
        'company_branch_id',
        'timeout_cancel',
    ];

    public function avito_order(): BelongsTo
    {
        return $this->belongsTo(AvitoOrder::class);
    }
}
