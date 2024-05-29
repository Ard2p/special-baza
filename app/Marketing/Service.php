<?php

namespace App\Marketing;

use App\Support\AttributesLocales\ServiceLocale;
use Illuminate\Database\Eloquent\Model;
use App\System\OrderableModel;

class Service extends Model
{

    use OrderableModel;
    protected $fillable = [
        'title', 'alias', 'keywords', 'h1',
        'description', 'content', 'image',
        'is_publish', 'button_text', 'form_text', 'settings', 'comment_label',
        'show_simple_form', 'show_big_form'
    ];
    private $setLocale = false;

    static function getSettings()
    {
        return [
            'color' => '#f4f4f4',
            'border' => '#f4f4f4',
            'button_color' => '#ee2b24',
            'button_text_color' => '#ffff',
        ];
    }

    function locale()
    {
        return  $this->hasMany(ServiceLocale::class);
    }


    function getSettingsAttribute($val)
    {
        $settings = $val ? json_decode($val, true) : [];
        foreach (self::getSettings() as $key => $option) {
            if (!isset($settings[$key])) {
                $settings[$key] = $option;
            }
        }
        return $settings;
    }

    function submittedForms()
    {
       return $this->hasMany(SubmitService::class);
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
                $this->image_alt = $en->image_alt;
                $this->content = $en->content;
                $this->form_text = $en->form_text;
                $this->button_text = $en->button_text;
                $this->comment_label = $en->comment_label;

                $this->setLocale =  true;
            }
        }

        return '';
    }

    function getAliasAttribute($val)
    {

        $this->localization();
        return $val;
    }

    function getTitleAttribute($val)
    {

        $this->localization();
        return $val;
    }
}
