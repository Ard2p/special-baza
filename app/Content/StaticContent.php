<?php

namespace App\Content;

use App\Article;
use App\Overrides\Model;
use App\System\OrderableModel;

class StaticContent extends Model
{

    use OrderableModel;


    protected $appends = ['is_static'];

    public function getRouteKeyName()
    {
        return 'alias';
    }

    function locale()
    {
        return $this->hasMany(StaticLocale::class, 'static_content_id');
    }

    function scopeWhereAlias($q, $alias)
    {
        return $q->where('alias', $alias);
    }

    function getMenuTitleAttribute($val)
    {
        if (!\App::isLocale('ru')) {
            $en = $this->locale->where('locale', \App::getLocale())->first();
            if ($en) {
               return $en->menu_title;
            }
        }
        return $val;
    }

    function localization()
    {
        if (!\App::isLocale('ru')) {
            $en = $this->locale->where('locale', \App::getLocale())->first();
            if ($en) {
                $this->title = $en->title;
                $this->keywords = $en->keywords;
                $this->description = $en->description;
                $this->h1 = $en->h1;
                $this->image_alt = $en->image_alt;
                $this->content = $en->content;
            }
        }
    }


    function getIsStaticAttribute($val = null)
    {
        $this->localization();
        return $val;
    }

    function subMenuArticles()
    {
        return $this->belongsToMany(Article::class, 'menu_article')->where('articles.is_publish', '=', 1);
    }

    function subMenuArticlesSections()
    {
        return $this->belongsToMany(ArticleSection::class, 'menu_article_section');
    }
}
