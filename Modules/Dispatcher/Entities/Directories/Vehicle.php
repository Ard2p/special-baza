<?php

namespace Modules\Dispatcher\Entities\Directories;

use App\City;
use App\Machines\Brand;
use App\Machines\Type;
use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Dispatcher\Entities\Lead;

class Vehicle extends Model
{

    protected $table = 'dispatcher_vehicles';
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type_id',
        'brand_id',
        'comment',
        'contractor_id'
    ];

    protected $appends = ['image'];

    function category()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    function scopeForBranch($q, $branch_id = null)
    {
        $branch_id = $branch_id ?: app()->make(RequestBranch::class)->companyBranch->id;
        return $q->whereHas('contractor', function ($q) use ($branch_id) {
            $q->where('company_branch_id', $branch_id);
        });
    }

    function getImageAttribute()
    {
        return $this->category->thumbnail_link;
    }


}
