<?php

namespace App\Support;

use App\User;
use App\Overrides\Model;

class SubmitTicketPopup extends Model
{
   protected $fillable = [
       'user_id', 'ticket_popup_id', 'comment', 'url'
   ];

   function ticket()
   {
       return $this->hasOne(Ticket::class);
   }

   function ticket_popup()
   {
       return $this->belongsTo(TicketPopup::class);
   }

   function user()
   {
       return $this->belongsTo(User::class);
   }

}
