<?php


namespace Modules\CompanyOffice\Services;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\Customer\CustomerContract;

class ContractService
{

    /** @var CompanyBranch */
    private $companyBranch;
    private $data = [];

    private $entity;
    private ?Model $requisite;
    /**
     * @var mixed|null
     */
    private ?string $type;

    public function __construct(CompanyBranch $branch, Model $entity = null, Model $requisite = null, ?string $type = null)
    {
        $this->companyBranch = $branch;
        $this->requisite = $requisite;
        $this->type = $type;
        $this->entity = $entity;
        $this->setDataValues();
    }

    private function setDataValues()
    {
        $now = now();

        $this->data['yy'] = $now->format('y');
        $this->data['yyyy'] = $now->format('Y');
        $this->data['dd'] = $now->format('d');
        $this->data['hh'] = $now->format('H');
        $this->data['mm'] = $now->format('m');
        $this->data['lastInDay'] = $this->getLastIn('day') + 1;
        $this->data['lastInYear'] = $this->getLastIn('year') + 1;
        $this->data['lastInMonth'] = $this->getLastIn('month') + 1;
        $this->data['internalNumber'] = $this->getLastIn('internal') + 1;

        if(Auth::check()) {
            $this->data['managerId'] = Auth::id();
        }
    }

    private function getLastIn($type)
    {
        /** @var Builder $contractsQuery */
        $contractsQuery = CustomerContract::query()->whereHasMorph('customer', [get_class($this->entity)], function ($q) {
            $q->forBranch($this->companyBranch->id);
        });
        switch ($type) {
            case "year":
                return $contractsQuery->where('created_at', '>=', now()->startOfYear())->count();
            case "month":
                return $contractsQuery->where('created_at', '>=', now()->startOfMonth())->count();
            case "day":
                return $contractsQuery->where('created_at', '>=', now()->startOfDay())->count();
            case "internal":
                return $contractsQuery
                    ->when($this->requisite, fn(Builder $builder) => $builder->where('requisite_type', get_class($this->requisite))->where('requisite_id', $this->requisite->id))
                    ->when($this->type, fn(Builder $builder) => $builder->where('type', $this->type))
                    //->where('customer_id', $this->entity->id)
                    ->max('number');
        }
    }

    function getValueByMask(string $mask, $additionalValues = [])
    {
        $data = array_merge($this->data, $additionalValues);

        $parsed = preg_replace_callback('/{(.*?)}/', function ($matches) use ($data)  {
            [$shortCode, $index] = $matches;

            if (isset($data[$index])) {
                return $data[$index];
            } else {
//                throw new Exception("Shortcode {$shortCode} not found in template id {$this->id}", 1);
            }

        }, $mask);

        return $parsed;
    }
}