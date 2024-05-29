<?php

namespace Modules\RestApi\Http\Controllers;

use App\Article;
use App\Content\StaticContent;
use App\Support\KnowledgeBase;
use App\System\SystemModule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\RestApi\Entities\Content\Tag;
use Modules\RestApi\Entities\KnowledgeBase\Category;
use Modules\RestApi\Entities\KnowledgeBase\Faq;

class ArticlesController extends Controller
{
    function getNews(Request $request)
    {
        $articles = Article::active()
            ->forDomain($request->header('Domain', null))
            ->forDistrict($request->header('city_id', null))
            ->contentType($request->input('type', null));

        if($request->filled('tag')){
            $articles->whereHas('tags', function ($q) use ($request){
                $q->whereName($request->input('tag'));
            });
        }

        $articles = $articles->orderBy('created_at', 'desc')->paginate($request->count ?: 3);

        $articles->each(function ($item) {

            return $item->localization();
        });
        $collection = collect($articles);
        $collection->put('types', [
            [
                'alias' => 'news',
                'title' => trans('transbaza_home.news_title'),
                'count' => Article::where('type', 'news')->count()
            ],
        /*    [
                'alias' => 'article',
                'title' => trans('transbaza_home.article_title'),
                'count' => Article::where('type', 'article')->count()
            ],*/
            [
                'alias' => 'notes',
                'title' => 'Release Notes',
                'count' => Article::where('type', 'notes')->count()
            ]
        ]);
        $tags = Tag::query()->whereHas('articles', function ($q) use ($request){
            $q->forDomain($request->header('domain', null))
                ->contentType($request->input('type', null));
        })->get();

        $collection->put('tags', $tags);
        return $collection;
    }


    function getStaticContent(Request $request, $alias)
    {
        $article = StaticContent::whereAlias($alias)
            ->firstOrFail();

        $article->localization();
        return $article;
    }

    function getArticle(Request $request, $alias)
    {

        return Article::active()
            ->forDomain($request->header('Domain', null))
            ->where('type', 'notes')
            ->whereAlias($alias)
            ->firstOrFail()
            ->localization();
    }


    function getNewsArticle(Request $request, $alias)
    {
        return Article::active()
            ->forDomain($request->header('Domain', null))
            ->where('type', 'news')
            ->whereAlias($alias)
            ->firstOrFail()
            ->localization();
    }

    function getContent(Request $request, $alias)
    {
        $article = Article::where('type', 'content')
            ->forDomain($request->header('Domain', null))
            ->whereAlias($alias)
            ->firstOrFail();

        $article->localization();
        return $article;
    }

    function knowledgeBase()
    {
        $modules = SystemModule::with(['content' => function($q){
            $q->where('is_publish', 1);
        }])->whereHas('content', function ($q){
            $q->where('is_publish', 1);
        })->get();

        return $modules;
    }

    function getFaq()
    {
        $faq = Category::query()->whereHas('content', function ($q) {
            $q->forRoles();
        })->with(['content' => function($q) {
            $q->forRoles();
        }])->forDomain()->get();

        return $faq;
    }
}
