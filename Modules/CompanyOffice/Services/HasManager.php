<?php


namespace Modules\CompanyOffice\Services;


use App\User;
use Illuminate\Support\Facades\Auth;

trait HasManager
{
   function scopeForManager($q, $user_id = null)
   {

       $user_id = $user_id ?: Auth::id();

       return $q->where('creator_id', $user_id);
   }

   function manager()
   {
       return $this->belongsTo(User::class, 'creator_id');
   }
}
