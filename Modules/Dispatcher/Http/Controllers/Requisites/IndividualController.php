<?php

namespace Modules\Dispatcher\Http\Controllers\Requisites;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Http\Requests\RequisitesRequest;

class IndividualController extends Controller
{
    private $contractor;

    public function __construct(Request $request)
    {
        $user = Auth::guard('api')->user();
        $this->contractor = Contractor::query()
            ->where('creator_id', $user->id)
            ->findOrFail($request->input('contractor_id'));
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {

        return $this->contractor->individual_requisites;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(RequisitesRequest $request)
    {
        $data = $request->all();

        $data['user_id'] = Auth::id();

        $requisite = $this->contractor->addIndividualRequisites($data);

        return response()->json($requisite->refresh());

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return $this->contractor->individual_requisites()->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(RequisitesRequest $request, $id)
    {
        $data = $request->all();

        $requisite = $this->contractor->individual_requisites()->findOrFail($id);

        $requisite->update($data);

        return response()->json($requisite);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->contractor->individual_requisites()->findOrFail($id)->delete();

        return response()->json();
    }
}
