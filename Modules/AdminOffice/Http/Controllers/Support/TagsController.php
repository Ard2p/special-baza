<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\RestApi\Entities\Content\Tag;

class TagsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $tags = Tag::query();

        return $tags->paginate($request->input('per_page', 15));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $tag = Tag::create(['name' => mb_ucfirst(mb_strtolower($request->input('name')))]);

        return $tag;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return Tag::query()->findOrFail($id);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $tag = Tag::query()->findOrFail($id);

        $request->validate(['name' => 'required|string|max:255']);

        $tag->update(['name' => mb_ucfirst(mb_strtolower($request->input('name')))]);

        return $tag;
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $tag = Tag::query()->findOrFail($id);

        $tag->delete();

        return response()->json();
    }
}
