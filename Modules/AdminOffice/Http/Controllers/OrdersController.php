<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Http\Controllers\Avito\Models\AvitoLog;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\System\Audit;
use App\User;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Http\Resources\AdminAvitoOrderResource;
use Modules\AdminOffice\Transformers\OrderResource;
use Modules\Orders\Entities\Order;

class OrdersController extends Controller
{
    function getOrders(Request $request, $id = null)
    {

        $orders = Order::with(['vehicles' => function ($q) {
            $q->with('user');
        }, 'user', 'city', 'region', 'types', 'customer_feedback', 'contractor'])->forDomain();

        if ($id) {
            return OrderResource::make($orders->findOrFail($id));
        }
        $filter = new Filter($orders);
        $filter->getLike([
            'address' => 'address',
        ])->getEqual([
            'region' => 'region_id',
            'city' => 'city_id',
            'status' => 'status',
        ])->getBetween([
            'amount_from' => 'amount',
            'amount_to' => 'amount',
        ], true);

        if (Auth::user()->isRegionalRepresentative() && !Auth::user()->isSuperAdmin()) {
            $orders->forRegionalRepresentative();
        }

        if ($request->filled('user')) {

            $orders->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->input('user')}%")
                    ->orWhere('phone', 'like', "%{$request->input('user')}%");
            });

        }


        return $orders->orderBy('created_at', 'DESC')->paginate($request->per_page ?: 10);
    }


    function updateOrder(Request $request, $id)
    {

    }

    function cancelOrder($id)
    {
        $order = Order::with('payment', 'user')
            ->whereStatus(Order::STATUS_ACCEPT)
            ->findOrFail($id);
        $order->cancel();

        return $order;
    }

    function getAudit(Request $request, $id)
    {
        $audits = Audit::query()->with('user')
            ->where('auditable_type', Order::class)
            ->where('auditable_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return $audits;
    }

    public function avitoOrders(Request $request)
    {
        $orders = AvitoOrder::query()->with('company_branch.company');
        (new Filter($orders))
            ->getLike([
                'avito_ad_id' => 'avito_ad_id',
                'avito_order_id' => 'avito_order_id',
                'order_id' => 'order_id',
                'created_at' => 'created_at',
            ]);
        if($request->filled('status')) {
            $orders->where('status', $request->status);
        }
        if($request->filled('cancel_reason')) {
            $orders->where('cancel_reason', $request->cancel_reason);
        }
        if($request->filled('pay_method')) {
            $orders->whereHas('order.invoices.pays', fn(Builder $builder) => $builder->where('method',  $request->pay_method));
        }
        if($request->filled('branch')) {
            $orders->whereHas('company_branch', fn(Builder $builder) => $builder->where('name', 'like', "%{$request->branch}%"));
        }
        if($request->filled('customer')) {
            $orders->whereHas('customer',
                fn(Builder $builder) => $builder
                    ->where('company_name', 'like', "%{$request->customer}%")
                    ->orWhere(DB::raw("CONCAT('Компания', ' #', `id`)"), 'like', "%{$request->customer}%")
            );
        }

        return AdminAvitoOrderResource::collection($orders->orderByDesc('id')->paginate($request->input('per_page', 20)));
    }

    public function avitoDump($id)
    {
        return \App\Http\Controllers\Avito\Resources\OrderResource::make(AvitoOrder::query()->findOrFail($id));
    }
    public function avitoLogs(Request $request)
    {
        return AvitoLog::query()->orderByDesc('id')->paginate($request->input('per_page', 20));
    }
}
