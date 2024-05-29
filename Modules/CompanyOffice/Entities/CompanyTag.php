<?php

namespace Modules\CompanyOffice\Entities;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Dispatcher\Entities\Customer;

class CompanyTag extends Model
{
    use BelongsToCompanyBranch;


    protected $table = 'company_tags';
    public $timestamps = false;
    protected $fillable = ['name', 'company_branch_id', 'color'];

    function customers()
    {
        return $this->morphedByMany(Customer::class, 'taggable', 'company_taggables');
    }

    static function createOrGet($array_names, $companyBranchId)
    {
        $collection = collect([]);
        foreach ($array_names as $name) {

            $collection->push(self::firstOrCreate(['name' => mb_ucfirst(mb_strtolower($name)), 'company_branch_id' => $companyBranchId]));

        }

        return $collection;
    }
}
