<?php

namespace Modules\AdminOffice\Http\Controllers\Settings;

use App\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{


    public function index(Request $request)
    {
        $roles = Role::query()->with('access_blocks');

        return $roles->paginate($request->input('per_page', 10));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {

        $errors = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:255|unique:roles,alias'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        $role = Role::create([
            'name' => $request->input('name'),
            'can_delete' => true,
            'dashboard_access' => filter_var($request->input('dashboard_access'), FILTER_VALIDATE_BOOLEAN),
            'alias' => generateChpu($request->input('alias')),
        ]);

        return \response()->json($role);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $role = Role::with('access_blocks')->findOrFail($id);

        $role->setAppends(['blocks']);

        return $role;
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
        $errors = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:255|unique:roles,alias,' . $id,
            'blocks' => 'required|array',
            'blocks.*.types_list' => 'required|array',
            'blocks.*.types_list.*.value' => 'nullable|in:show,create,delete',
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        DB::beginTransaction();

        $role->update([
            'name' => $request->input('name'),
            'alias' => ($role->isDeletable() ? generateChpu($request->input('alias')) : $role->alias),
            'dashboard_access' => filter_var($request->input('dashboard_access'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $role->access_blocks()->detach();

        foreach ($request->input('blocks') as $block) {
            foreach ($block['types_list'] as $type) {
                if (filter_var($type['enable'], FILTER_VALIDATE_BOOLEAN)) {
                    $role->access_blocks()->attach( [$block['id'] => ['type' => $type['value']]]);
                }
            }
        }

        DB::commit();

        return $role;
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if($role->isDeletable()){
            $role->delete();

            return  response()->json();
        }
        return  response()->json([], 400);
    }
}
