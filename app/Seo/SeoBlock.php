<?php

namespace App\Seo;

use App\Overrides\Model;
use Illuminate\Support\Str;

class SeoBlock extends Model
{
    protected $fillable = [
        'url',
        'comment',
        'html_top',
        'html_bottom',
        'is_active'
    ];

    static function renderTop()
    {
        $url = \Request::path();

        $seo = self::whereUrl($url)->whereIsActive(1)->first();
        if ($seo) {
            return view('includes.seo_top', ['content' => $seo->html_top])->render();
        }
    }

    static function renderBottom($fromApi = false)
    {

        $url = !$fromApi || !isset($_SERVER['HTTP_REFERER'])
            ? \Request::path()
            : trim(parse_url($_SERVER['HTTP_REFERER'])['path'], '/');
        $data = self::whereIsActive(1)->get()->sortByDesc(function($item) {
            return strlen($item->url);
        });

        foreach ($data as $item) {
            if($item->url === 'spectehnika' && $item->url !== $url){
                continue;
            }
            if ((Str::startsWith($url, $item->url))) {
                return view('includes.seo_top', ['content' => $item->html_bottom])->render();
            }
        }

    }

}
