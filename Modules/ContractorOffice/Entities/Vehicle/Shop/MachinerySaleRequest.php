<?php

namespace Modules\ContractorOffice\Entities\Vehicle\Shop;

use App\Machinery;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\CompanyOffice\Entities\Company\SaleContract;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Services\ContractTrait;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Transformers\VehicleSearch;

class MachinerySaleRequest extends Model
{

    use BelongsToCompanyBranch, HasManager, HasContacts, InternalNumbering, ContractTrait;

    protected $fillable = [
        'date',
        'customer_id',
        'phone',
        'pay_type',
        'email',
        'currency',
        'contact_person',
        'status',
        'reject_type',
        'creator_id',
        'company_branch_id',
    ];

    protected $dates = ['date'];

   // protected $with = ['positions', 'customer', 'manager'];

    protected $appends = ['status_lng'];

    function customer()
    {
        return $this->belongsTo(Customer::class);
    }


    function positions()
    {
        return $this->hasMany(MachinerySaleRequestPosition::class, 'machinery_sale_request_id');
    }

    function sales()
    {
        return $this->hasMany(MachinerySale::class, 'machinery_sale_request_id');
    }

    function contract()
    {
        return $this->morphOne(SaleContract::class, 'owner');
    }

    function getAvailableMachineries()
    {

        $ids = [];
        $machineries = collect();
        foreach ($this->positions as $position) {
            $m = Machinery::query()
                ->forBranch()
                ->where('read_only', 0)
                ->where('available_for_sale', true)
                ->categoryBrandModel($position->category_id, $position->brand_id, $position->model_id)
                ->whereNotIn('id', $ids)
                ->get();

            $ids += $m->pluck('id')->toArray();
            $machineries = $machineries->merge($m);
        }
        return VehicleSearch::collection($machineries);
    }

    function getStatusLngAttribute()
    {
        $array = Lead::getStatuses();

        $key = array_search($this->status, array_column($array, 'value'));

        return $array[$key]['name'] ?? '';
    }

    function createDefaultContract()
    {

        $settings = $this->company_branch->getSettings();

        if($settings->default_machinery_sale_contract_url) {

            $path = config('app.upload_tmp_dir') . "/{$this->id}_machinery_sale_contract.docx";

            $contract = Storage::disk()->get($settings->default_machinery_sale_contract_url);

            Storage::disk()->put($path, $contract);

            $this->addContract($settings->default_machinery_sale_contract_name ?: 'Договор', $path);

            // $this->lead->addContract('Договор', $path);
        };
        //  $contract = Storage::disk('public_disk')->get('documents/default_contract.docx');


        return $this;
    }


    function sale($cart)
    {

        $this->update([
            'status' => Lead::STATUS_ACCEPT
        ]);
        /** @var MachinerySale $sale */
        $sale  = new MachinerySale([
            'date' => $this->date,
            'pay_type' => $this->pay_type,
            'currency' => $this->currency,
            'status' => Order::STATUS_ACCEPT,
            'machinery_sale_request_id' => $this->id,
            'account_number' => $cart['account_number'],
            'account_date' => $cart['account_date'],
            'creator_id' => Auth::id() ?: $this->creator_id,
            'company_branch_id' => $this->company_branch_id,
        ]);
        $sale->customer()->associate($this->customer);
        $sale->save();

        if($this->contract) {
            $this->company_branch->generateSaleContract($sale, $this->contract->url, $this->contract->title);
        }


        $i = 0;
        foreach ($cart['items'] as $item) {
            /** @var Machinery $machinery */
            $machinery = Machinery::query()->forBranch($this->company_branch_id)->findOrFail($item['id']);

            $sale->operations()->save(new OperationCharacteristic([
                'machinery_id' => $machinery['id'],
                'cost' => numberToPenny($item['cost']),
                'engine_hours' => $machinery->technicalWork ? $machinery->technicalWork->engine_hours : 0,
                'type' => 'used',
                'application_id' => ++$i,
            ]));

            $machinery->setReadOnly();
        }

        return $this;
    }

}
