<?php

namespace App\Http\Controllers\Avito\Models;

use App\Machinery;
use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class AvitoAd extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'machinery_id',
        'avito_id'
    ];

    protected $casts =[
    ];

    public function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
}
