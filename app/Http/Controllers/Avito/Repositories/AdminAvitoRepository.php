<?php

namespace App\Http\Controllers\Avito\Repositories;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Exceptions\CustomerNotFound;
use App\Http\Controllers\Avito\Exceptions\MachineryNotFound;
use App\Http\Controllers\Avito\Models\AvitoHoldsHistory;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Models\AvitoOrderHistory;
use App\Http\Controllers\Avito\Models\AvitoStat;
use App\Http\Controllers\Avito\Requests\AdminGetOrdersRequest;
use App\Http\Controllers\Avito\Resources\OrderResource;
use App\Machinery;
use App\Overrides\Model;
use App\Service\DaData;
use App\User\IndividualRequisite;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Entities\Company\ContactEmail;
use Modules\CompanyOffice\Entities\Company\ContactPhone;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\Payment;
use Modules\RestApi\Entities\Domain;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use phpDocumentor\Reflection\Types\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class AdminAvitoRepository
{
    public function __construct() {}

    public function getOrdersFilters(AdminGetOrdersRequest $request)
    {
        $avitoOrders = AvitoOrder::query()
            ->with([
                'company_branch.company', 'company_branch.company.domain.countries',
                'order', 'order.contractor', 'order.invoices','customer'
            ]);

        if($request->filled('avito_ad_id')) {
            $avitoOrders->where('avito_ad_id', 'like', "%{$request->avito_ad_id}%");
        }

        if($request->filled('avito_order_id')) {
            $avitoOrders->where('avito_order_id', 'like', "%{$request->avito_order_id}%");
        }

        if($request->filled('order_id')) {
            $avitoOrders->where('order_id', 'like', "%{$request->order_id}%");
        }

        if($request->filled('customer_type')) {
           $customer_type  = match ($request->customer_type) {
                'individual' => 1,
                'legal' => 2,
                'entity' => 3
            };
            $avitoOrders->where('customer_type', $customer_type);
        }

        if($request->filled('date_from')){
            $avitoOrders->whereDate('created_at', '>=',
                Carbon::parse($request->date_from)->format('Y-m-d'));
        }
        if($request->filled('date_to')){
            $avitoOrders->whereDate('created_at', '<=',
                Carbon::parse($request->date_to)->format('Y-m-d'));
        }

        if($request->filled('date_order_from')){
            $avitoOrders->whereHas('order', fn(Builder $builder) =>
                $builder->whereDate('date_from', '>=',
                    Carbon::parse($request->date_order_from)->format('Y-m-d'))
            );
        }
        if($request->filled('date_order_from_to')){
            $avitoOrders->whereHas('order', fn(Builder $builder) =>
                $builder->whereDate('date_from', '<=',
                    Carbon::parse($request->date_order_from_to)->format('Y-m-d'))
                );
        }

        if($request->filled('reject_type')) {
            $avitoOrders->whereHas('order.workers', function(Builder $builder) use ($request){
                $builder->where('reject_type', $request->reject_type);
            });
        }

        if($request->filled('machinery_type')) {
            $avitoOrders->whereHas('order', function (Builder $builder) use ($request){
                $builder->whereHas('workers', function(Builder $builder) use ($request){
                    $builder->whereHas('worker', function(Builder $builder) use ($request){
                        $builder->where('name', 'like', "%{$request->machinery_type}%");
                    });
                });
            });

//            $avitoOrders->whereHas('order.workers.worker._type', fn(Builder $builder) =>
//            $builder->where('name', 'like', "%{$request->machinery_type}%"));
        }

        if($request->filled('city')) {
            $avitoOrders->whereHas('order.city', fn(Builder $builder) =>
            $builder->where('name', 'like', "%{$request->city}%"));
        }

        if($request->filled('customer')) {
            $avitoOrders->whereHas('order.customer',
                fn(Builder $builder) => $builder
                    ->where('company_name', 'like', "%{$request->customer}%")
                    ->orWhere('name', 'like', "%{$request->customer}%")
            );
        }


        if($request->filled('source')){
            $avitoOrders->whereHas('order', function(Builder $builder) use ($request){
                $builder->where('from', $request->source);
            });
        }

        if($request->filled('phone')) {
            $avitoOrders->whereHas('order.customer', function(Builder $builder) use ($request){
                $builder->where('phone','like', "%{$request->phone}%");

            });
        }

        if($request->filled('inn')) {
            $avitoOrders->where('inn', 'like', "%{$request->inn}%");
        }

        if($request->filled('contractor')) {
            $avitoOrders->whereHas('order.contractorRequisite',
                fn(Builder $builder) => $builder
                    ->where('name', 'like', "%{$request->contractor}%")
                    ->orWhere('short_name', 'like', "%{$request->contractor}%")
            );
        }

        if($request->filled('pay_method')) {
            $avitoOrders->whereHas('order.invoices.pays', fn(Builder $builder) => $builder->where('method',  $request->pay_method));
        }

        if($request->filled('status')) {
            $avitoOrders->whereIn('status', (array)$request->status);
//            $avitoOrders->where('status', $request->status);
        }

        if($request->filled('cancel_reason')) {
            $avitoOrders->where('cancel_reason', $request->cancel_reason);
        }

        if($request->filled('canceled_from')){
            $avitoOrders->whereDate('canceled_at', '>=',
                Carbon::parse($request->canceled_from)->format('Y-m-d'));
        }
        if($request->filled('canceled_to')){
            $avitoOrders->whereDate('canceled_at', '<=',
                Carbon::parse($request->canceled_to)->format('Y-m-d'));
        }

        if($request->filled('requisites_type')) {
            if($request->requisites_type == 'entity')
                $avitoOrders->has('customer.entity_requisites');
            if($request->requisites_type == 'individual')
                $avitoOrders->has('customer.individual_requisites');
            if($request->requisites_type == 'legal')
                $avitoOrders->has('customer.international_legal_requisites');
        }

        return $avitoOrders;
    }

    public function getOrders(AdminGetOrdersRequest $request){
        $avitoOrders = $this->getOrdersFilters($request);

        return $avitoOrders
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 20));
    }

    public function getOrdersResults(AdminGetOrdersRequest $request){
        $avitoOrdersResults = $this->getOrdersFilters($request);

//        $avitoOrdersResults->withSum('order', 'amount');
        return $avitoOrdersResults->get();
    }

}
