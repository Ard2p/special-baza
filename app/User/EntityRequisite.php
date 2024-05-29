<?php

namespace App\User;

use App\Rules\Inn;
use App\Rules\Kpp;
use App\Rules\Ogrn;
use App\Service\RequestBranch;
use App\User;
use Carbon\Carbon;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Dispatcher\Entities\DispatcherInvoice;

class EntityRequisite extends Model
{

    use SoftDeletes, BelongsToCompanyBranch;


    protected $with = ['bankRequisites'];

    protected $fillable = [
        'creator_id', 'name', 'phone', 'email', 'inn', 'kpp', 'ogrn',
        'director_short', 'director_genitive', 'short_name', 'actual_address',
        'director', 'booker', 'bank', 'bik', 'ks', 'rs', 'active', 'register_address', 'company_branch_id', 'charter', 'director_position', 'director_position_genitive', 'vat_system',
        'last_update', 'status',
        'contract_number_template',
        'contract_sale_number_template',
        'contract_service_number_template',
        'contract_default_name',
        'contract_service_default_name',
    ];

    protected $appends  =['requisite_short_type'];

    static $requiredFields = [
        'name'     => 'required|string',
        'inn'      => [
            'required'
        ],
        'kpp'      => 'required|string|digits:9',
        'ogrn'     => 'required|string|digits_between:13,15',
        'director' => 'required|string',
        'booker'   => 'required|string',
        'email'    => 'nullable|email',
        'bank'     => 'required|string',
        'bik'      => 'required|digits:9',
        'ks'       => 'required|string|digits:20',
        'rs'       => 'required|string|digits:20',

    ];

    static $attributesName = [
        'name'                   => 'Наименование',
        'inn'                    => 'ИНН',
        'kpp'                    => 'КПП',
        'ogrn'                   => 'ОГРН',
        'director'               => 'Ф.И.О. Ген. Директора',
        'booker'                 => 'Ф.И.О. Главного бухгалтера',
        'bank_requisites.*.name' => 'Банк',
        'bank_requisites.*.bik'  => 'БИК',
        'bank_requisites.*.ks'   => 'КС',
        'bank_requisites.*.rs'   => 'Счет',
    ];

    static function getValidationRules()
    {
        $isNotRf = app(RequestBranch::class)?->companyBranch?->is_not_rf;

        return [
            'name'                   => 'required|string|max:255',
            'actual_address'         => 'nullable|string|max:255',
            'short_name'             => 'nullable|string|max:255',
            'inn'                    => [
                'required',
                new Inn()
            ],
            'kpp'                    => [
                'nullable',
                new Kpp(),
            ],
            'ogrn'                   => [
                'nullable',
                new Ogrn(),
            ],
            'email'                  => 'nullable|email',
            'director'               => 'nullable|string|max:255',
            'booker'                 => 'nullable|string|max:255',
            'bank_requisites.*.name' => 'required|string|max:255',
            'bank_requisites.*.bik'  => $isNotRf ? 'nullable' :'nullable|digits:9',
            'bank_requisites.*.ks'   => $isNotRf ? 'nullable|string' : 'nullable|string|digits:20',
            'bank_requisites.*.rs'   => $isNotRf ? 'nullable|string' : 'nullable|string|digits:20',

            'status'            => 'nullable',
            'register_address'  => 'nullable|string|max:255',
            'director_short'    => 'nullable|string|max:255',
            'director_genitive' => 'nullable|string|max:255',
            'charter'           => 'nullable|string|max:255',
            'director_position' => 'nullable|string|max:255',
            'vat_system'        => 'nullable|in:cashless_without_vat,cashless_vat',
        ];

    }


    function getLastUpdateAttribute($val)
    {
        return $val
            ? Carbon::parse($val)->format('Y-m-d')
            : '';
    }

    function customerLegalRequisite()
    {
        return $this->morphOne(DispatcherInvoice::class, 'requisite');
    }

    function owner()
    {
        return $this->morphTo('requisite');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    function scopeGetActive($q)
    {
        return $q->where('active', 1);
    }

    function scopeForUser(
        $q,
        $id)
    {
        return $q->where('user_id', $id);
    }

    function bankRequisites()
    {
        return $this->morphMany(BankRequisite::class, 'owner');
    }

    function addBankRequisites($data)
    {

        foreach ($data as $bank_requisite) {
            User\BankRequisite::query()->updateOrCreate([
                'id' => $bank_requisite['id'] ?? null,
            ], [
                'name'       => $bank_requisite['name'],
                'bik'        => $bank_requisite['bik'] ?? null,
                'ks'         => $bank_requisite['ks'] ?? null,
                'rs'         => $bank_requisite['rs'] ?? null,
                'owner_type' => self::class,
                'owner_id'   => $this->id
            ]);
        }

    }

    public function getContractNumberTemplateAttribute ($val)
    {
        return $val ?: '';
    }

    public function getContractSaleNumberTemplateAttribute ($val)
    {
        return $val ?: '';
    }

    public function getContractDefaultNameAttribute ($val)
    {
        return $val ?: '';
    }

    public function getContractServiceDefaultNameAttribute ($val)
    {
        return $val ?: '';
    }

    public function getContractServiceNumberTemplateAttribute ($val)
    {
        return $val ?: '';
    }

    public function getRequisiteShortTypeAttribute ()
    {
        return "legal_{$this->id}";
    }
}
