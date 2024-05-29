<?php

namespace App\Marketing;

use App\Marketing\Mailing\ListName;
use App\Marketing\Mailing\Template;
use App\Support\AttributesLocales\ContactFormLocale;
use Illuminate\Database\Eloquent\Model;

class ContactForm extends Model
{
    protected $fillable = [
        'name', 'button_text', 'form_text',
        'url', 'include_sub', 'position',
        'collect_name', 'collect_email', 'collect_phone',
        'template_id', 'settings',
        'comment_label', 'collect_comment', 'phone_template_id', 'is_publish'
    ];

    private $setLocale = false;

    function locale()
    {
        return $this->hasMany(ContactFormLocale::class);
    }

    function phone_book()
    {
        return $this->hasOne(ListName::class)->where('list_names.type', '=', 'phone');
    }

    function email_book()
    {
        return $this->hasOne(ListName::class)->where('list_names.type', '=', 'email');
    }

    function template()
    {
        return $this->belongsTo(Template::class)->where('templates.type', '=', 'email');
    }

    function phone_template()
    {
        return $this->belongsTo(Template::class, 'phone_template_id');
    }

    function sendingMails()
    {
        return $this->hasMany(SendingMails::class);
    }

    function sendingSms()
    {
        return $this->hasMany(SendingSms::class);
    }

    static function renderTop()
    {
        $url = \Request::path();
        $forms = self::wherePosition('top')->whereIsPublish(1)->get();
        foreach ($forms as $form) {
            if ($form->include_sub) {
                if (\Request::is($form->url . '/*')) {
                    return view('marketing.contact_form', ['form' => $form])->render();
                }
            }
            if ($url === $form->url) {
                return view('marketing.contact_form', ['form' => $form])->render();
            }
        }
    }

    function getIncludeSubAttribute($val)
    {
        $this->localization();
        return $val;
    }

    static function renderBottom()
    {
        $url = \Request::path();
        $forms = self::wherePosition('bottom')->whereIsPublish(1)->get();
        foreach ($forms as $form) {
            if ($form->include_sub) {
                if (\Request::is($form->url . '/*')) {
                    return view('marketing.contact_form', ['form' => $form])->render();
                }
            }
            if ($url === $form->url) {
                return view('marketing.contact_form', ['form' => $form])->render();
            }
        }
    }

    static function getSettings()
    {
        return [
            'color' => '#f4f4f4',
            'border' => '#f4f4f4',
        ];
    }

    function getSettingsAttribute($val)
    {
        return $val ? json_decode($val, true) : self::getSettings();
    }

    function localization()
    {
        if(!\App::isLocale('ru') && !$this->setLocale){
            $en = $this->locale->where('locale', \App::getLocale())->first();
            if($en){
                $this->name = $en->name;
                $this->button_text = $en->button_text;
                $this->form_text = $en->form_text;
                $this->comment_label = $en->comment_label;
                $this->template_id = $en->template_id;

                $this->setLocale =  true;
            }
        }

        return '';
    }
}
