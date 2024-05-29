<?php

namespace Modules\Dispatcher\Entities;

use App\Machinery;
use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Machines\OptionalAttribute;
use App\Machines\Type;
use Illuminate\Database\Eloquent\Model;

class PreLeadPosition extends Model
{

    public $timestamps = false;
    protected $table = 'dispatcher_pre_leads_positions';

 //  protected $with = [
 //      'attributes',
 //      'category',
 //      'model',
 //      'machinery',
 //  ];
    protected $fillable = [
        'pre_lead_id',
        'category_id',
        'brand_id',
        'model_id',
        'machinery_id',
        'count',
        'comment',
        'date_from',
        'time_from',
        'order_type',
        'order_duration',
    ];

    protected $casts = [
        'date_from' => 'date:Y-m-d',
        'time_from' => 'date:H:i',
    ];

    protected $appends = ['category_options'];

    function preLead()
    {
        return $this->belongsTo(PreLead::class);
    }

    function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    function category()
    {
        return $this->belongsTo(Type::class, 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function attributes()
    {
        return $this->belongsToMany(OptionalAttribute::class, 'dispatcher_pre_leads_attributes')->withPivot('value')->orderBy('priority');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function optional_attributes()
    {
        return $this->belongsToMany(OptionalAttribute::class, 'dispatcher_pre_leads_attributes')->withPivot('value')->orderBy('priority');
    }

    function model()
    {
        return $this->belongsTo(MachineryModel::class, 'model_id');
    }

    function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }

    function getCategoryOptionsAttribute()
    {
        if($this->optional_attributes) {
            return collect($this->optional_attributes)->map(function ($item) {

                return $item->pivot->value ? $item->full_name : null;
            })->filter(fn($item) => !!$item);
        }

        return  null;
    }

}
