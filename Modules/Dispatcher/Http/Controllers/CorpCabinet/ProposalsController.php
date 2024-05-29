<?php

namespace Modules\Dispatcher\Http\Controllers\CorpCabinet;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\AdminOffice\Entities\Filter;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Transformers\CorpCabinet\ProposalInfo;

class ProposalsController extends Controller
{
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
        /** @var Builder $proposals */
        $proposals = Lead::query()->forCompany($this->currentCompany->id);

        $filter = new Filter($proposals);

        $filter->getLike([
            'customer_name' => 'customer_name',
            'address' => 'address',
            'id' => 'internal_number',
            'comment' => 'comment',
        ])->getEqual([
            'status' => 'status'
        ])->getDateBetween([
            'date_from' => 'start_date'
        ]);

        $proposals->whereHasMorph('customer', [Customer::class], function (Builder $q) use ($customerId) {
            $q->where('dispatcher_customers.id', $customerId);
            $q->whereHas('corpUsers', function (Builder $q) {
                $q->where('users.id', Auth::id());
            });
        });
        return ProposalInfo::collection($proposals->paginate($request->perPage ?: 15));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('dispatcher::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('dispatcher::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('dispatcher::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
