<?php

namespace App\Support;

use App\Article;
use App\Overrides\Model;

class ArticleLocale extends Model
{
   protected $fillable = [
       'title', 'keywords', 'description',
       'h1', 'image_alt', 'content',
       'locale', 'user_id', 'article_id',
   ];

   function article()
   {
       return $this->belongsTo(Article::class);
   }
}
