<?php

namespace App\Http\Controllers;

use App\Article;
use App\Content\StaticContent;
use App\Option;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index($alias)
    {

        $article = Article::where('alias', $alias)
            ->whereIsStatic(1)
            ->where('is_publish', 1)->first();
        if (!$article) {
            $article = StaticContent::where('alias', $alias)->firstOrFail();
        }

        $response = response()->json();
        if (request()->server('HTTP_IF_MODIFIED_SINCE')) {

            $value = Carbon::parse(request()->server('HTTP_IF_MODIFIED_SINCE'));

            if ($value > $article->updated_at) {
                $response->setNotModified();
            }

        }
        return $response;
    }

    function getArticle($alias)
    {
        $article = Article::where('alias', $alias)
            ->whereIsArticle(1)
            ->where('is_publish', 1)
            ->firstOrFail();

        $response = response('OK');
        if (request()->server('HTTP_IF_MODIFIED_SINCE')) {

            $value = Carbon::parse(request()->server('HTTP_IF_MODIFIED_SINCE'));

            if ($value > $article->updated_at) {
                $response->setNotModified();
            }

        }
        return $response;
    }

    function getNewsArticle($alias)
    {
        $article = Article::where('alias', $alias)
            ->whereIsNews(1)
            ->where('is_publish', 1)
            ->firstOrFail();

        $response = response()->json();
        if (request()->server('HTTP_IF_MODIFIED_SINCE')) {

            $value = Carbon::parse(request()->server('HTTP_IF_MODIFIED_SINCE'));

            if ($value > $article->updated_at) {
                $response->setNotModified();
            }

        }
        return $response;
    }

    public function static($article)
    {
        $article = StaticContent::where('alias', $article)->firstOrFail();

        $response = response()->json();


        if (request()->server('HTTP_IF_MODIFIED_SINCE')) {

            $value = Carbon::parse(request()->server('HTTP_IF_MODIFIED_SINCE'));

            if ($value > $article->updated_at) {
                $response->setNotModified();
            }

        }
        //   if($article->updated_at < Carbon::now())
        return $response; //->setNotModified();
    }


    function getArticles()
    {
        $article = (object) [];
        $options = \Config::get('global_options');

        $article->title = $options->where('key', 'static_meta_title')->first()->value ?? '';
        $article->keywords = $options->where('key', 'static_meta_keywords')->first()->value ?? '';
        $article->description = $options->where('key', 'static_meta_description')->first()->value ?? '';
        $article->h1 = $options->where('key', 'static_meta_h1')->first()->value ?? '';
        $articles = Article::where('is_publish', 1)
            ->where('is_static', 0)
            ->where('is_article', 1)
            ->where('only_menu', 0)
            ->where('is_news', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json();
    }

    function getNews()
    {
        $articles = Article::where('is_publish', 1)
            ->where('is_static', 0)
            ->where('is_article', 0)
            ->where('only_menu', 0)
            ->where('is_news', 1)
            ->orderBy('created_at', 'desc')
            ->get();
        $article = (object) [];
        $options = \Config::get('global_options');
        $article->title = $options->where('key', 'article_meta_title')->first()->value ?? '';
        $article->keywords = $options->where('key', 'article_meta_keywords')->first()->value ?? '';
        $article->description = $options->where('key', 'article_meta_description')->first()->value ?? '';
        $article->h1 = $options->where('key', 'article_meta_h1')->first()->value ?? '';

        return response()->json();
    }
}
