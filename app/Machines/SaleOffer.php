<?php

namespace App\Machines;

use App\Machinery;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SaleOffer extends Model
{
   protected $fillable = [
       'email',
       'phone',
       'comment',
       'user_id',
       'machinery_id',
   ];

   function machine()
   {
       return $this->belongsTo(Machinery::class, 'machinery_id');
   }

   function user()
   {
       return $this->belongsTo(User::class);
   }
}
