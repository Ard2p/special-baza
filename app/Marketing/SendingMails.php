<?php

namespace App\Marketing;

use App\Marketing\Mailing\EmailList;
use App\Marketing\Mailing\Template;
use Illuminate\Database\Eloquent\Model;

class SendingMails extends Model
{
    protected $fillable = [
        'email_list_id', 'template_id',
        'confirm_status', 'is_watch', 'watch_at',
        'hash', 'contact_form_id'
    ];

    protected $appends =[
        'status', 'email', 'comment'
    ];
    function email_book()
    {
        return $this->belongsTo(EmailList::class, 'email_list_id');
    }

    function template()
    {
        return $this->belongsTo(Template::class)->where('templates.type', '=', 'email');
    }

    function submit_form()
    {
        return $this->hasOne(SubmitContactForm::class, 'sending_mail_id');
    }



    function getCommentAttribute()
    {
       return $this->submit_form->comment ?? '';
    }


    function form()
    {
        return $this->belongsTo(ContactForm::class, 'contact_form_id');
    }

    function getEmailAttribute()
    {
        return $this->email_book->email ?? '';
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


}
