<?php

namespace App\Support;

use App\Overrides\Model;

class TicketPopup extends Model
{
    protected $fillable = [

        'support_category_id',
        'button_text',
        'form_text',
        'url',
        'include_sub',
        'comment_label',
        'settings',
        'is_publish',
    ];

    function support_category()
    {
        return $this->belongsTo(SupportCategory::class);
    }


    static function getSettings()
    {
        return [
            'color' => '#f4f4f4',
            'border' => '#f4f4f4',
            'button_color' => '#ee2b24',
            'button_text_color' => '#ffff',
        ];
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

    static function renderPopup()
    {
        if(!\Auth::check()){
            return '';
        }
        $url = \Request::path();
        $forms = self::whereIsPublish(1)->whereId(1)->get();
        foreach ($forms as $form) {
            if ($form->include_sub) {
                if (\Request::is('*/' .$form->url . '/*')) {
                    return view('includes.ticket.ticket_simple_form', ['form' => $form])->render();
                }
            }
            if ($url === $form->url) {
                return view('includes.ticket.ticket_simple_form', ['form' => $form])->render();
            }
        }
    }

}
