<?php

namespace Modules\Dispatcher\Entities\Customer;

use App\Machines\Sale;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use http\Exception\InvalidArgumentException;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CompanyOffice\Services\ContractService;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Profiles\Entities\UserNotification;

class CustomerContract extends Model
{

    use SoftDeletes;

    protected $table = 'dispatcher_customer_contracts';

    protected $fillable = [
        'prefix',
        'number',
        'postfix',
        'customer_id',
        'customer_type',
        'current_number',
        'last_application_id',
        'created_at',
        'type',
        'name',
        'start_date',
        'end_date',
        'subject_type',
        'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean'
    ];

    protected $appends = ['requisite_instance', 'full_number'];

    protected $with = ['requisite'];
    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {
            $companyService = new CompaniesService($model->customer->company_branch->company);

            /*            $companyService->addUsersNotification(
                            trans('user_notifications.customer_new_contract', ['id' => $model->customer->internal_number]),
                            Auth::user() ?: null,
                            UserNotification::TYPE_INFO,
                            $model->customer->generateCompanyLink(),
                            $model->customer->company_branch);*/
        });

        self::updated(function (self $model) {
//            $companyService = new CompaniesService($model->customer->company_branch->company);
//
//            $companyService->addUsersNotification(
//                trans('user_notifications.customer_update_contract', ['id' => $model->customer->internal_number]),
//                Auth::user()
//                    ?: null,
//                UserNotification::TYPE_INFO,
//                $model->customer->generateCompanyLink(),
//                $model->customer->company_branch);
        });
    }

    static function generateContract(
        Model $customer,
              $fullNumber = null,
              $date = null,
              $internalNumber = null,
              $type = 'rent',
                $requisite = null,
    $data = [])
    {
        $lock = Cache::lock("generate_contract_{$customer->id}", 10);


        try {
            if ($lock->get()) {

                /** @var CompanyBranch $branch */
                $branch = $customer->company_branch;
                $settings = $branch->getSettings();

                $serv = new ContractService($branch, $customer, $requisite, $type);

                if($requisite) {
                    $maskTemplate = $type === 'rent' ? $requisite->contract_number_template : $requisite->contract_service_number_template;
                    $maskNameTemplate = $type === 'rent' ? $requisite->contract_default_name : $requisite->contract_service_default_name;

                }
                if(!($maskTemplate ?? false)) {
                    $maskTemplate = $type === 'rent' ? $settings->contract_number_template : $settings->contract_service_number_template;
                    $maskNameTemplate = $type === 'rent' ? $settings->contract_default_name : $settings->contract_service_default_name;

                    if(!$maskTemplate) {
                        throw ValidationException::withMessages(['errors' => "Отсутствует маска договора."]);
                    }
                }

                $value = $serv->getValueByMask($maskTemplate  ?? '');
                $contractName = $serv->getValueByMask($maskNameTemplate ?? '');

                $max =
                    self::query()->where('type', $type)
                        ->when($requisite, fn(Builder $builder) => $builder->where('requisite_type', get_class($requisite))->where('requisite_id', $requisite->id))
                        ->whereHasMorph('customer', [get_class($customer)], function ($q) use ($customer) {
                        $q->forBranch($customer->company_branch_id);
                    })
                        //->where('customer_id', $customer->id)
                        ->max('number');

                $contract = self::create([
                    'number'         => ($internalNumber
                        ?: $max + 1),
                    'customer_id'    => $customer->id,
                    'customer_type'  => get_class($customer),
                    'type'  => $type,
                    'current_number' => $fullNumber
                        ?: $value,
                    'created_at'     => ($date
                        ?: now()),
                    'name' => $contractName,
                    ...$data,
                    'subject_type' => $data['subject_type'] ?? 'contract',
                    'last_application_id' => 0
                ]);

                if($requisite) {
                    $contract->requisite()->associate($requisite);
                    $contract->save();

                }

                $lock->release();
            }
        } catch (\Exception $exception) {
            $lock->release();
            logger($exception->getMessage(), $exception->getTrace());
            throw new \InvalidArgumentException();
        }


        return $contract;
    }

    function getFullNumberAttribute()
    {
        return $this->current_number;
    }

    function getNameAttribute($val)
    {
        return $val ?: $this->current_number;
    }

    function getOldNumber()
    {
        return "{$this->prefix}{$this->number}{$this->postfix}";
    }

    function customer()
    {
        return $this->morphTo();
    }

    function requisite()
    {
        return $this->morphTo('requisite');
    }

    function getRequisiteInstanceAttribute()
    {

      return match($this->requisite_type) {
          EntityRequisite::class => 'legal_'. $this->requisite_id,
          IndividualRequisite::class => 'individual_'. $this->requisite_id,
          default => ''
      };

    }

    public function services()
    {
        return $this->hasMany(ServiceCenter::class, 'contract_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'contract_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'customer_contract_id');
    }

    public function sales()
    {
        return $this->hasMany(MachinerySale::class, 'contract_id');
    }
}
