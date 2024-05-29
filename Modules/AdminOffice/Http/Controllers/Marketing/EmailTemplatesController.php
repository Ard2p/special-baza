<?php

namespace Modules\AdminOffice\Http\Controllers\Marketing;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Entities\Marketing\Mailing\Template;
use Spatie\Permission\Models\Role;

class EmailTemplatesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $templates = Template::query()->where('type', 'email')->orderBy('id', 'desc');


        return $templates->paginate($request->input('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'name' => 'required|string|max:255',
            'text' => 'required|string',
            'roles' => 'nullable|array'
        ]);

        DB::beginTransaction();

        $roles = Role::query()->whereIn('name', $request->input('roles') ?: [])
            ->get();
        /** @var Template $template */
        $template = Template::create($request->only(['domain_id', 'name', 'text', 'system_alias'])
            + [
                'type' => 'email'
            ]
        );


        $template->syncRoles($roles);
        DB::commit();

        return response()->json($template);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return Template::query()->where('type', 'email')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $template = Template::query()->where('type', 'email')->findOrFail($id);

        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'name' => 'required|string|max:255',
            'text' => 'required|string',
            'roles' => 'nullable|array'
        ]);

        $values = $template->can_delete
            ? $request->only(['domain_id', 'name', 'text',])
            : $request->only(['name', 'text',]);

        DB::beginTransaction();
        /** @var Template $template */

        $template->update($values);

        $roles = Role::query()->whereIn('name', $request->input('roles') ?:[])->get();

        $template->syncRoles($roles);

        DB::commit();


        return response()->json($template);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        Template::query()
            ->where('type', 'email')
            ->where('can_delete', true)
            ->findOrFail($id)->delete();

        return response();
    }
}
