<?php

namespace App\Support\AttributesLocales;

use Illuminate\Database\Eloquent\Model;

class ContactFormLocale extends Model
{
  protected $fillable = [
      'name',
      'button_text',
      'form_text',
      'comment_label',
      'locale',
      'template_id',
      'contact_form_id'
  ];
}
