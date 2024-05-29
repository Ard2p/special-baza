<?php

namespace Modules\AdminOffice\Http\Controllers\Faq;

use App\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\RestApi\Entities\KnowledgeBase\Faq;

class ContentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $faq = Faq::query()->with('roles')->forDomain();

        return $faq->paginate($request->per_page ?: 15);
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
            'content' => 'required|string',
            'roles' => 'nullable|array',
            'category_id' => 'required|exists:knowledge_base_categories,id',
        ]);

        DB::beginTransaction();
        $faq = Faq::create($request->only([
            'name',
            'content',
            'category_id',
        ]));


        $roles = Role::whereIn('id', $request->input('roles'))->get();

        $faq->roles()->sync($roles->pluck('id'));

        DB::commit();

        return response()->json($faq);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return Faq::with('roles')->findOrFail($id);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'roles' => 'nullable|array',
            'category_id' => 'required|exists:knowledge_base_categories,id',
        ]);

        DB::beginTransaction();

        $faq->update($request->only([
            'name',
            'content',
            'category_id',
        ]));

        $roles = Role::whereIn('id', $request->input('roles'))->get();

        $faq->roles()->sync($roles->pluck('id'));

        DB::commit();

        return response()->json($faq);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return response()->json();
    }
}
