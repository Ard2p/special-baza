<?php

namespace App\Content;

use App\Overrides\Model;

class ArticleSection extends Model
{
    function getNameAttribute($val)
    {
       return $this->alias === 'news' ? trans('transbaza_home.news') : trans('transbaza_home.article_title');
    }
}
