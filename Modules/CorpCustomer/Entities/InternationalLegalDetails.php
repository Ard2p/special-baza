<?php

namespace Modules\CorpCustomer\Entities;

use App\Rules\Inn;
use App\Rules\Kpp;
use App\Rules\Ogrn;
use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class InternationalLegalDetails extends Model
{

    use BelongsToCompanyBranch;

    protected $fillable = [
        'account_name',
        'account',
        'swift',
        'beneficiary_bank',
        'code',
        'bank_address',
        'short_name',
        'signatory_name',
        'signatory_position',
        'legal_address',
        'actual_address',
        'vat_system',
        'user_id',
        'company_branch_id',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function (self $model) {
            $model->user_id = Auth::id();
            return $model;
        });
    }

    static function getValidationRules()
    {
        return [
            'account_name' => 'required|string|max:255',
            'account' => 'required|string|max:255',
            'swift' => 'nullable|string|max:255',
            'beneficiary_bank' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'legal_address' => 'required|string|max:255',
            'signatory_name' => 'required|string|max:255',
            'signatory_position' => 'required|string|max:255',

            'actual_address' => 'nullable|string|max:255',
            'vat_system' => 'nullable|in:cashless_without_vat,cashless_vat',
        ];
    }


    function user()
    {
        return $this->belongsTo(User::class);
    }
}
