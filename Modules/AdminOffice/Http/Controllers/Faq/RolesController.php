<?php

namespace Modules\AdminOffice\Http\Controllers\Faq;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\RestApi\Entities\KnowledgeBase\Role;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $roles = Role::query();

        return $request->filled('get_all')
            ? $roles->get()
            : $roles->paginate($request->per_page ?: 15);
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
        ]);

        $role = Role::create([
            'name' => $request->input('name'),
        ]);


        return response()->json($role);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return Role::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {

        $role = Role::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',

        ]);

        $role->update([
            'name' => $request->input('name'),
        ]);


        return response()->json($role);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        $role->delete();

        return response()->json();
    }
}
