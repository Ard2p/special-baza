<?php

namespace Modules\AdminOffice\Http\Controllers\Faq;

use App\Helpers\RequestHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\RestApi\Entities\KnowledgeBase\Category;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $categories = Category::query()->forDomain();

        return $request->filled('get_all')
            ? $categories->get()
            : $categories->paginate($request->per_page ?: 15);
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
            'knowledge_base_role_id' => 'nullable|exists:knowledge_base_roles,id',

        ]);

       $category =  Category::create([
            'name' => $request->input('name'),
            'knowledge_base_role_id' => $request->input('knowledge_base_role_id'),
            'domain_id' => RequestHelper::requestDomain('id')
        ]);


       return  response()->json($category);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return Category::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {

        $category = Category::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'knowledge_base_role_id' => 'nullable|exists:knowledge_base_roles,id',
        ]);

        $category->update([
            'name' => $request->input('name'),
            'knowledge_base_role_id' => $request->input('knowledge_base_role_id'),
        ]);


        return  response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        $category->delete();

        return  response()->json();
    }
}
