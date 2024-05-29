<?php

namespace Modules\CompanyOffice\Entities\Directories;

use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Machines\Type;
use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompany;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class SlangCategory extends Model
{

    protected $table = 'company_slang_categories';
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'category_id',
        'brand_id',
        'model_id',
        'company_id',
        'insurance_premium',
        'rent_days_count',
        'service_days_count',
    ];

    function category()
    {
        return $this->belongsTo(Type::class);
    }

    function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    function model()
    {
        return $this->belongsTo(MachineryModel::class, 'model_id');

    }
}
