<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Article;
use App\Support\ArticleLocale;
use App\Support\FederalDistrict;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\Content\ArticleGallery;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Transformers\Content;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Entities\Content\Tag;

class ArticlesController extends Controller
{

    public function __construct(Request $request)
    {
        $data = $request->all();
        $data = array_map(function ($val) {
            return $val === 'null' || $val === 'undefined' ? '' : $val;
        }, $data);
        $request->merge($data);
    }

    function getArticles(Request $request, $id = null)
    {

        $articles = Article::query()->with('domain');
        if ($id) {
            return Content::make($articles->with('locale',  'federal_districts', 'tags')->findOrFail($id));
        }
        if ($request->filled('type')) {
            if($request->input('type') === 'notes') {
                $articles->whereIn('type', ['notes', 'content']);
            }else {
                $articles->where('type', $request->input('type'));
            }

        }
        $filter = new Filter($articles);

        $filter->getLike([
            'name' => 'title'
        ]);


        return $articles->orderBy('created_at', 'DESC')->paginate($request->per_page ?: 10);
    }


    function updateLocale(Request $request, $id)
    {
        $article = ArticleLocale::findOrFail($id);

        $rules = [
            'title' => 'required|string|max:255',
            'keywords' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'h1' => 'required|string|max:255',
            'image_alt' => 'nullable|string|max:255',
            'content' => 'required|string',
        ];
        $errors = Validator::make($request->all(), $rules)->errors()->getMessages();
        if ($errors) {
            return \response()->json($errors, 400);
        }

        $article->update([
            'title' => $request->input('title'),
            'keywords' => $request->input('keywords'),
            'description' => $request->input('description'),
            'h1' => $request->input('h1'),
            'image_alt' => $request->input('image_alt'),
            'content' => $request->input('content'),
        ]);

        return response()->json($article);
    }


    function updateArticle(Request $request, $id)
    {
        /** @var Article $article */
        $article = Article::findOrFail($id);

        $rules = [
            'title' => 'required|string|max:255',
            'alias' => 'required|string|max:255|unique:articles,alias,' . $id,
            'keywords' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'h1' => 'required|string|max:255',
            'image_alt' => 'nullable|string|max:255',
            'is_publish' => 'nullable',
            'content' => 'required|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'federal_districts' => 'nullable|array',
            'federal_districts.*' => 'nullable|exists:federal_districts,id',
            'type' => 'required|in:news,article,content,notes',
            'galleries' => 'nullable|array'
        ];

        $request->validate($rules);

        DB::beginTransaction();
        $article->update([
            'title' => $request->input('title'),
            'alias' => $request->input('alias'),
            'keywords' => $request->input('keywords'),
            'description' => $request->input('description'),
            'h1' => $request->input('h1'),
            'image_alt' => $request->input('image_alt'),
            'is_publish' => filter_var($request->input('is_publish'), FILTER_VALIDATE_BOOLEAN),
            'content' => $request->input('content'),
            'image' => $request->input('image'),
            'type' => $request->input('type'),
            'domain_id' => $request->input('domain_id')
        ]);

        $tags = Tag::createOrGet($request->input('tags'));

        $article->tags()->sync($tags->pluck('id'));

        $article->federal_districts()->sync(FederalDistrict::whereIn('id', $request->input('federal_districts'))->pluck('id'));

        foreach ($request->galleries as $gallery) {

            $gal = (!empty($gallery['id']))
                ? $article->galleries()->findOrFail($gallery['id'])
                : new ArticleGallery(['images' => $gallery['images']]);

            $gal->article()->associate($article->id);

            $gal->save();
        }

        DB::commit();
        $article->refresh();
        return response()->json($article);

    }


    function createArticle(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'required|in:news,article,content,notes',
            'alias' => 'required|string|max:255|unique:articles,alias',
            'keywords' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'h1' => 'required|string|max:255',
            'image_alt' => 'nullable|string|max:255',
            'is_publish' => 'nullable',
            'content' => 'required|string',
            'image' => 'nullable|string',
            'galleries' => 'nullable|array'
        ];
        $request->validate($rules);

        DB::beginTransaction();

        $article = Article::create([
            'title' => $request->input('title'),
            'alias' => $request->input('alias'),
            'keywords' => $request->input('keywords'),
            'description' => $request->input('description'),
            'h1' => $request->input('h1'),
            'image_alt' => $request->input('image_alt'),
            'is_publish' => filter_var($request->input('is_publish'), FILTER_VALIDATE_BOOLEAN),
            'content' => $request->input('content'),
            'image' => $request->input('image'),
             'type' => $request->input('type'),
            'domain_id' => $request->input('domain_id')
        ]);

        if($request->filled('galleries')) {
            foreach ($request->galleries as $gallery) {
                $new = new ArticleGallery([
                    'images' => $gallery['images']
                ]);
                $new->article()->associate($article->id);

                $new->save();
            }
        }

        DB::commit();

        return response()->json($article);
    }

    function createArticleLocale(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'locale' => 'required|in:en',
            'keywords' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'h1' => 'required|string|max:255',
            'image_alt' => 'nullable|string|max:255',
            'content' => 'required|string',
            'article_id' => 'required|exists:articles,id',
        ];
        $errors = Validator::make($request->all(), $rules)->errors()->getMessages();
        if ($errors) {
            return \response()->json($errors, 400);
        }

        $article = ArticleLocale::create([
            'title' => $request->input('title'),
            'keywords' => $request->input('keywords'),
            'description' => $request->input('description'),
            'h1' => $request->input('h1'),
            'image_alt' => $request->input('image_alt'),
            'content' => $request->input('content'),
            'locale' => $request->input('locale'),
            'article_id' => $request->input('article_id'),
        ]);

        return response()->json($article);
    }

    function deleteArticle($id)
    {
        Article::findOrFail($id)->delete();

        return response()->json();
    }

    function deleteGallery($id)
    {
        $gallery = ArticleGallery::query()->findOrFail($id);

        $gallery->remove();

        return response()->json();
    }
}
