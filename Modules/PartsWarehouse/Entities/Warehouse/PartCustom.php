<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class PartCustom extends Model
{
    use BelongsToCompanyBranch;

    protected $table = 'warehouse_part_customs';

    protected $fillable = [
        'name',
        'vendor_code',
        'part_id',
        'company_branch_id',
    ];

    function part()
    {
        return $this->belongsTo(Part::class);
    }

}
