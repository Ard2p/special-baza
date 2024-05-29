<?php

namespace Modules\PartsWarehouse\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminOffice\Entities\Filter;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Transformers\PostingResource;

class PostingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $posting = Posting::query()
            ->withAmount($request->part_id ?: null)
            ->withCost($request->part_id ?: null)
            ->forBranch()->orderBy('created_at', 'desc');

        if($request->filled('part_id')) {

            $posting->whereHas('stockItems', function (Builder $q) use ($request) {
                $q->where('part_id', $request->input('part_id'));
            });


            return  PostingResource::collection($posting->get());
        }
        $filter = new Filter($posting);
        $filter->getEqual([
           'parts_provider_id' => 'parts_provider_id',
        ])->getLike([
            'internal_number' => 'internal_number',
            'account_number' => 'account_number',
        ]);

        return PostingResource::collection($posting->paginate($request->per_page ?: 15));
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
        $posting = Posting::query()
            ->with('stockItems')
            ->withAmount(null)
            ->withCost(null)
                ->forBranch()
            ->findOrFail($id);

        return PostingResource::make($posting);
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
