<?php

namespace Modules\RestApi\Entities\KnowledgeBase;

use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;

class Faq extends Model
{

    protected $table = 'knowledge_base_faq';

    protected $fillable = [
        'name',
        'content',
        'category_id'
    ];

    function category()
    {
        return $this->belongsTo(Category::class);
    }


    function roles()
    {
        return $this->belongsToMany(\App\Role::class, 'knowledge_base_faq_roles', 'knowledge_base_faq_id');
    }



    function scopeForDomain($q)
    {
        return $q->whereHas('category', function ($q) {
            $q->forDomain();
        });
    }


    function scopeForRoles($q)
    {
        return $q->whereHas('roles', function ($q) {
            $roles = Auth::check() ? Auth::user()->roles->pluck('id') : [];
            $q->whereIn('id', $roles);
        });
    }


}
