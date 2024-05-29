<?php

namespace App\Support;

use App\Support\AttributesLocales\KnowledgeBaseLocale;
use App\System\SystemFunction;
use App\System\SystemModule;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{

    private $setLocale = false;

    function locale()
    {
        return $this->hasMany(KnowledgeBaseLocale::class, 'knowledge_base_id');

    }


    function systemModule()
    {
        $this->localization();
        return $this->belongsTo(SystemModule::class);
    }


    function systemFunction()
    {
        return $this->belongsTo(SystemFunction::class);
    }


    function localization()
    {
        if(!\App::isLocale('ru') && !$this->setLocale){
            $en = $this->locale->where('locale', \App::getLocale())->first();
            if($en){
                $this->title = $en->title;
                $this->keywords = $en->keywords;
                $this->description = $en->description;
                $this->h1 = $en->h1;
                $this->content = $en->content;

                $this->setLocale =  true;
            }
        }

        return '';
    }

  /*  function getIdAttribute($val)
    {

        $this->localization();
        return $val;
    }*/

    function getTitleAttribute($val)
    {

        $this->localization();
        return $val;
    }

}
