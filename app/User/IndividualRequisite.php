<?php

namespace App\User;

use App\User;
use Carbon\Carbon;
use DateTime;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Modules\CompanyOffice\Entities\Company\ContactEmail;
use Modules\CompanyOffice\Entities\Company\ContactPhone;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;

class IndividualRequisite extends Model
{

    use SoftDeletes, BelongsToCompanyBranch;


    const TYPE_UNKNOWN       = 'unknown';
    const TYPE_PERSON       = 'person';
    const TYPE_ENTREPRENEUR = 'entrepreneur';

    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {
            $model->moveScans();
        });

        self::updated(function (self $model) {
            $model->moveScans();
        });
    }

    protected $fillable = [
        'creator_id', 'firstname', 'middlename', 'surname', 'gender', 'birth_date',
        'passport_number', 'passport_date', 'issued_by', 'register_address', 'kp',
        'ogrnip', 'ogrnip_date', 'type', 'passport_validity',
        'scans', 'active', 'inn', 'bank', 'bik', 'ks', 'rs', 'company_branch_id',
        'signatory_name', 'signatory_short', 'signatory_genitive', 'full_name', 'short_name', 'department_code',

        'birth_place',
        'passport_type',
        'resident_card_citizen', 'resident_card_register_number', 'resident_card_date_of_issue',
        'resident_card_register_number', 'resident_card_valid_until', 'resident_card_place_of_birth',
        'contract_number_template',
        'contract_sale_number_template',
        'contract_service_number_template',
        'contract_default_name',
        'contract_service_default_name',
        'position',
        'position_genitive',
    ];

    static $requiredFields = [
        'firstname'                     => 'required|string|max:255',
        'middlename'                    => 'nullable|string|max:255',
        'surname'                       => 'required|string|max:255',
        'passport_validity'             => 'nullable|date',
        //'gender' => 'required|string|in:male,female',
        'birth_date'                    => 'nullable|date',
        'passport_number'               => 'nullable|string|max:255',
        'passport_date'                 => 'nullable|string|max:255',
        'issued_by'                     => 'nullable|string|max:255',
        'register_address'              => 'nullable|string|max:255',
        'birth_place'                   => 'nullable|string',
        'kp'                            => 'nullable|string',
        'bank'                          => 'nullable|string',
        'bik'                           => 'nullable|string|max:255',
        'ks'                            => 'nullable|string|max:255',
        'rs'                            => 'nullable|string|max:255',
        'ogrnip'                        => 'nullable|numeric|digits:15',
        'ogrnip_date'                   => 'nullable|date',
        'signatory_name'                => 'nullable|string|max:255',
        'signatory_short'               => 'nullable|string|max:255',
        'signatory_genitive'            => 'nullable|string|max:255',
        'full_name'                     => 'required|string|max:255',
        'short_name'                    => 'nullable|string|max:255',
        'scans'                         => 'nullable|array',
        'scans.*'                       => 'required|string',
        'passport_type'                 => 'nullable|in:main,foreign',
        'resident_card_citizen'         => 'nullable|string|max:255',
        'resident_card_register_number' => 'nullable|string|max:255',
        'resident_card_date_of_issue'   => 'nullable|date',
        'resident_card_valid_until'     => 'nullable|date',
        'resident_card_place_of_birth'  => 'nullable|string|max:255'
        /* 'inn' => 'required|digits:12',*/

    ];

    static $attributesName = [
        'firstname'        => 'Имя',
        'middlename'       => 'Отчество',
        'surname'          => 'Фамилия',
        'gender'           => 'Пол',
        'birth_date'       => 'Дата рождения',
        'passport_number'  => 'Номер паспорта',
        'passport_date'    => 'Дата выдачи',
        'issued_by'        => 'Орган выдачи',
        'register_address' => 'Адресс регистрации',
        'kp'               => 'КП',
    ];

    protected $with = ['driverLicence', 'bankRequisites'];

    protected $dates = ['birth_date', 'ogrnip_date', 'passport_date:Y-m-d'];

    protected $appends = ['date_birthday', 'vat_system'];

    protected $casts = [
        'ogrnip_date' => 'datetime:Y-m-d',
        'scans'       => 'array',

    ];

    function moveScans()
    {
        $tmp_path = config('app.upload_tmp_dir');
        $folder = "images/individual/{$this->id}/scans";
        $scans = $this->scans;

        $update = false;

        if (!$scans)
            $scans = [];

        foreach ($scans as $key => $scan) {

            $str = str_replace("{$tmp_path}/", '', $scan);
            $exist = Storage::disk()->exists($str);

            if (!Str::contains($scan, [$tmp_path])) {
                continue;
            }

            $ext = getFileExtensionFromString($scan);
            $current = "scan_{$key}.{$ext}";
            $new_name = "{$folder}/{$current}";

            if ($exist && $scan !== $new_name) {
                Storage::disk()->move($scan, $new_name);
                $scans[$key] = $new_name;
                $update = true;
            }
        }

        if ($update) {
            $this->update(['scans' => $scans]);
        }

        $files = Storage::disk()->files($folder);

        foreach ($files as $originalName) {

            $file = $originalName;

            if (!in_array($file, $scans)) {
                Storage::disk()->delete($originalName);
            }
        }
    }

    static function getRulesByType($type)
    {
        $data = self::$requiredFields;
        if ($type === self::TYPE_PERSON) {
            unset(
                $data['register_address'],
                $data['inn'],
                $data['kp'],
                $data['bank'],
                $data['bik'],
                $data['ks'],
                $data['rs'],
                $data['signatory_name'],
                $data['signatory_short'],
                $data['signatory_genitive'],
                $data['gender'],
                $data['full_name']
                // $data['ogrnip'],
                // $data['ogrnip_date']
            );
        }
        if(!$type || $type === self::TYPE_UNKNOWN) {
            unset(
                $data['register_address'],
                $data['inn'],
                $data['kp'],
                $data['bank'],
                $data['bik'],
                $data['ks'],
                $data['rs'],
                $data['signatory_name'],
                $data['signatory_short'],
                $data['signatory_genitive'],
                $data['gender'],
                $data['surname'],
                $data['firstname'],
                $data['full_name'],
            );
        }

        return $data;
    }

    function getFullNameAttribute()
    {
        return "{$this->surname} {$this->firstname} {$this->middlename}";
    }

    function getBirthDateAttribute($val)
    {
        return $val
            ? Carbon::parse($val)->format('Y-m-d')
            : null;
    }

    public function getDateBirthdayAttribute()
    {

        return $this->birth_date
            ? (Carbon::parse($this->birth_date))->format('Y-m-d')
            : '';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    function scopeCurrentUser($q)
    {
        return $q->where('creator_id', Auth::user()->id);
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

    function owner()
    {
        return $this->morphTo('requisite');
    }

    function getVatSystemAttribute()
    {
        return Price::TYPE_CASHLESS_WITHOUT_VAT;
    }

    function getPassportValidityAttribute($val)
    {
        if ($val) {
            return Carbon::parse($val)->format('Y-m-d');
        }

        return $val;
    }

    function driverLicence()
    {
        return $this->morphOne(DriverLicence::class, 'owner')->withDefault(['serial' => '']);
    }

    function bankRequisites()
    {
        return $this->morphMany(BankRequisite::class, 'owner');
    }

    function phones()
    {
        return $this->hasMany(ContactPhone::class);
    }

    function emails()
    {
        return $this->hasMany(ContactEmail::class);
    }

    function principals()
    {
        return $this->hasMany(PrincipalDoc::class);
    }

    function contactOwners()
    {
     return $this->morphedByMany(Customer::class, 'owner', 'individual_requisites_contacts_pivot')
         ->withPivot('type');

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
}
