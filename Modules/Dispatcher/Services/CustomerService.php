<?php


namespace Modules\Dispatcher\Services;


use App\Machinery;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Http\Request;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\Order;

class CustomerService
{
    /**
     * @var Customer
     */
    private $customer;


    static function filter(Builder $query, Request $request)
    {


        if ($request->filled('category_has_orders')) {

            $query->whereHas('orders', function ($q) use ($request) {
                $q->whereHas('components', function (Builder $q) use ($request) {
                    $q->whereHasMorph('worker', [Machinery::class], function ($q) use ($request) {
                        $q->whereType($request->input('category_has_orders'));
                    });
                });
            });
        }

        if ($request->filled('category_only_leads')) {
            $query->whereHas('leads', function (Builder $q) use ($request) {
                $q->whereHas('categories', function (Builder $q) use ($request) {

                    $q->where('types.id', $request->input('category_only_leads'));

                })->whereDoesntHave('orders');
            });
        }
        $tags = is_array($request->input('tags')) ? $request->input('tags') : [];//explode(',', $request->input('tags'));
        if ($tags) {
            //$tags = is_array($request->input('tags')) ? $request->input('tags') : explode(',', $request->input('tags'));
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        if(filter_var($request->input('archive'), FILTER_VALIDATE_BOOLEAN)) {
            $query->whereHas('orders', function ($q) {
                $q->where('status', Order::STATUS_DONE);
            });
        }

        if (toBool($request->input('empty_bank_requisites'))) {
            $query->where(function (Builder $q) {
                $q->whereHas('entity_requisites', function (Builder $q) {
                    $q->whereNull('bank')
                        ->orWhereNull('bik')
                        ->orWhereNull('ks')
                        ->orWhereNull('rs');

                })->orWhereHas('international_legal_requisites', function (Builder $q) {
                    $q->whereNull('swift')
                        ->orWhereNull('code')
                        ->orWhereNull('beneficiary_bank');

                })->orWhereHas('individual_requisites', function (Builder $q) {
                    $q->whereNull('bik')
                        ->orWhereNull('bank')
                        ->orWhereNull('ks')
                        ->orWhereNull('rs');
                });
            });
        }
        if (toBool($request->input('empty_requisites'))) {
            $query->whereDoesntHave('entity_requisites')
                ->whereDoesntHave('international_legal_requisites')
                ->whereDoesntHave('individual_requisites');
        }
        return $query;
    }

    public function __construct(Model $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Добавление клиента в черный список
     * @param string $action
     * @return CustomerService
     */
    function blackList($action = 'add')
    {
        /** @var Customer $customer */

        $action === 'add'
            ? $this->customer->addToBlackList()
            : $this->customer->removeFromBlackList();

        return $this;
    }

    function changeContractSettings($data)
    {
        $contract = $data['id'] ?? null ? $this->customer->contracts
            ->where('type', $data['order_type'])
            ->where('id', $data['id'])
           ->first() : null;
        if (!$contract) {
            $contract = Customer\CustomerContract::generateContract($this->customer, $data['number'], $data['created_at'], $data['internal_number'], $data['order_type']);
        } else {
            $contract->update([
                'current_number' => $data['number'],
                'number' =>  $data['internal_number'],
                'last_application_id' =>  $data['last_application_id'],
                'created_at' => $data['created_at'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => $data['is_active'],
            ]);
        }


         $reqData = explode('_', $data['requisite_instance']);
         if ($req = $this->customer->company_branch->findRequisiteByType($reqData[1], $reqData[0])) {
             $contract->requisite()->associate($req);
             $contract->save();
         }



        return $this;
    }

    function updateLastApplicationId($id)
    {
        $this->customer->update([
            'last_application_id' => $id
        ]);

        return $this;
    }
}
