<?php

namespace Modules\RestApi\Entities\KnowledgeBase;

use App\Overrides\Model;

class Role extends Model
{
    protected $table = 'knowledge_base_roles';

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];


    function categories()
    {
        return $this->hasMany(Category::class, 'knowledge_base_role_id');
    }


}
