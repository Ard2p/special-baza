<?php

namespace App\Marketing;

use App\Marketing\Mailing\EmailList;
use App\Marketing\Mailing\PhoneList;
use Illuminate\Database\Eloquent\Model;

class SubmitContactForm extends Model
{
   protected $fillable = [
       'email_list_id',
       'phone_list_id',
       'name',
       'comment',
       'contact_form_id',
       'sending_mail_id',
       'sending_sms_id',
   ];

   function email_book()
   {
       return $this->belongsTo(EmailList::class);
   }

   function phone_book()
   {
       return $this->belongsTo(PhoneList::class);
   }

    function contact_form()
    {
        return $this->belongsTo(ContactForm::class);
    }
}
