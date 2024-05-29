<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Seo\SeoBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\Filter;

class SeoBlockController extends Controller
{

    function get(Request $request, $id = null)
    {
      $blocks = SeoBlock::query();
      if($id){
          return $blocks->findOrFail($id);
      }

      $filter = new Filter($blocks);

      $filter->getLike([
          'name' => 'url',
          'comment' => 'comment',
      ]);

      return $blocks->paginate($request->per_page ?: 10);
    }

    public function create(Request $request)
    {
        if ($request->filled('url')) {
            $request->merge([
                'url' => trim($request->url, '/')
            ]);
        }

        $request->validate([
            'url' => 'required|unique:seo_blocks'
        ]);


        $block = SeoBlock::create([
            'url' => $request->url,
            'comment' => $request->comment,
            'html_top' => $request->html_top,
            'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            'html_bottom' => $request->html_bottom,

        ]);

        return response()->json($block);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if ($request->filled('url')) {
            $request->merge([
                'url' => trim($request->url, '/')
            ]);
        }
        $errors = Validator::make($request->all(), [
            'url' => 'required|unique:seo_blocks,id,' . $id,
        ])->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);


        $block = SeoBlock::findOrFail($id);
        $block->update([
            'url' => $request->url,
            'comment' => $request->comment,
            'html_top' => $request->html_top,
            'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            'html_bottom' => $request->html_bottom,
        ]);

        return response()->json(['message' => 'Сохранено']);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function delete($id)
    {
        SeoBlock::findOrFail($id)->delete();
    }
}
