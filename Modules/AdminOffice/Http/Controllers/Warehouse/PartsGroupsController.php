<?php

namespace Modules\AdminOffice\Http\Controllers\Warehouse;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\PartsWarehouse\Entities\Warehouse\PartsGroup;

class PartsGroupsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $groups = PartsGroup::query()->with('children');

        if($request->filled('parent_id')) {
            $groups->where('parent_id', $request->input('parent_id'));
        }else {
            $groups->whereNull('parent_id');
        }

        return $groups->get()->map(function ($item) {
            $item->children = [];
            return $item;
        });
    }



    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
       $request->validate([
           'name' => 'required|string|max:255',
           'parent_id' => 'nullable|exists:warehouse_parts_groups,id',
       ]);

       $group = PartsGroup::create([
           'name' => $request->input('name'),
           'parent_id' => $request->input('parent_id'),
       ]);

       return  response()->json($group);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('adminoffice::show');
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
