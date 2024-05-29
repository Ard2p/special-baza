<?php

namespace Modules\RestApi\Entities\KnowledgeBase;

use App\Helpers\RequestHelper;
use App\Overrides\Model;
use Modules\RestApi\Entities\Domain;

class Category extends Model
{

    protected $table = 'knowledge_base_categories';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'knowledge_base_role_id',
        'domain_id',
    ];


    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    function content()
    {
        return $this->hasMany(Faq::class);
    }


    function role()
    {
        return $this->belongsTo(Role::class, 'knowledge_base_role_id');
    }


    function scopeForDomain($q, $domain_id = null)
    {
        $domain_id = $domain_id ?: RequestHelper::requestDomain()->id;

        if (!$domain_id) {
            return $q;
        }
        return $q->where('domain_id', $domain_id);
    }

}
