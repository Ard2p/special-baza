<?php

namespace Modules\ContractorOffice\Entities\Sets;

use App\Machines\MachineryModel;
use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\PartsWarehouse\Entities\Warehouse\Part;

class MachinerySet extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'name',
        'prices',
        'company_branch_id',
    ];

    protected $casts = [
        'prices' => 'object'
    ];


    function equipments()
    {
        return $this->hasMany(MachinerySetEquipment::class);
    }

}
