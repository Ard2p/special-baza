<?php

namespace Modules\Dispatcher\Http\Controllers\CorpCabinet;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Transformers\CorpCabinet\OrderInfo;
use Modules\Orders\Entities\Order;

class OrdersController extends Controller
{

    /** @var Company  */
    private $currentCompany;

    public function __construct()
    {
        $this->currentCompany = app(RequestBranch::class)->company;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, $customerId)
    {
        $orders = Order::query();

        $orders->whereHasMorph('customer', [Customer::class], function (Builder $q) use ($customerId) {
            $q->where('dispatcher_customers.id', $customerId);
            $q->whereHas('corpUsers', function (Builder $q) {
                $q->where('users.id', Auth::id());
            });
        });

        $filter = new Filter($orders);
        if ($request->filled('archive')) {
            $orders->archive();
        } else {
            $filter->getEqual([
                'status' => 'status'
            ]);
        }
        $filter->getLike([
            'internal_number' => 'internal_number'
        ]);
        return OrderInfo::collection($orders->paginate($request->perPage ?: 15));
    }


    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request, $customerId, $id)
    {
        $orders = Order::query();

        $orders->whereHasMorph('customer', [Customer::class], function (Builder $q) use ($customerId) {
            $q->where('dispatcher_customers.id', $customerId);
            $q->whereHas('corpUsers', function (Builder $q) {
                $q->where('users.id', Auth::id());
            });
        });

        return OrderInfo::make($orders->findOrFail($id));
    }

   function getContract(Request $request, $customerId, $id)
   {

       $order = Order::query()->whereHasMorph('customer', [Customer::class], function (Builder $q) use ($customerId) {
           $q->where('dispatcher_customers.id', $customerId);
           $q->whereHas('corpUsers', function (Builder $q) {
               $q->where('users.id', Auth::id());
           });
       })->findOrFail($id);
       $url = $order->getContractUrl();

       return $url ? response()->json([
           'url' => $url
       ])
           : response()->json([], 400);
   }


   function getDocuments(Request $request, $customerId, $id)
   {
       $order = Order::query()->whereHasMorph('customer', [Customer::class], function (Builder $q) use ($customerId) {
           $q->where('dispatcher_customers.id', $customerId);
           $q->whereHas('corpUsers', function (Builder $q) {
               $q->where('users.id', Auth::id());
           });
       })->findOrFail($id);

       return $order->documents;
   }
}
