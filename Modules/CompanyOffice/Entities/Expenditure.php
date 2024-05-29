<?php

namespace Modules\CompanyOffice\Entities;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class Expenditure extends Model
{

    use BelongsToCompanyBranch;

    protected $fillable = [
        'name'
    ];

    function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }
}
