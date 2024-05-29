<?php

namespace App\Support\AttributesLocales;

use Illuminate\Database\Eloquent\Model;

class SimpleProposalLocale extends Model
{
   protected $fillable = [
       'simple_proposal_id',
       'button_text',
       'locale',
       'form_text',
       'comment_label',
   ];
}
