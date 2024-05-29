<?php

namespace App\Marketing;

use App\Marketing\Mailing\PhoneList;
use App\Marketing\Mailing\Template;
use Illuminate\Database\Eloquent\Model;

class SendingSms extends Model
{
    protected $fillable = [
        'contact_form_id',
        'phone_list_id',
        'template_id',
        'confirm_status',
        'is_watch',
        'watch_at',
        'hash',

    ];

    protected $appends =[
        'status',
        'delivery_status',
        'phone',
        'comment',
    ];
    function phone_book()
    {
        return $this->belongsTo(PhoneList::class, 'phone_list_id');
    }

    function template()
    {
        return $this->belongsTo(Template::class);
    }


    function form()
    {
        return $this->belongsTo(ContactForm::class, 'contact_form_id');
    }

    function getPhoneAttribute()
    {
        return $this->phone_book->phone ?? '';
    }

    function submit_form()
    {
        return $this->hasOne(SubmitContactForm::class);
    }



    function getCommentAttribute()
    {
        return $this->submit_form->comment ?? '';
    }

    function getStatusAttribute()
    {
        $text = '';
        switch ($this->confirm_status) {
            case 0:
                break;
            case 1:
                $text = 'Да';
                break;
            case 2:
                $text = 'Нет';
                break;
        }
        return $text;
    }

    function getDeliveryStatusAttribute()
    {

        return $this->is_watch ? 'Да' : 'Нет';
    }

}
