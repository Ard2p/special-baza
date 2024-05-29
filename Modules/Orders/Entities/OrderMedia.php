<?php

namespace Modules\Orders\Entities;

use Arhitector\Yandex\Disk;
use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;

class OrderMedia extends Model
{
    protected $fillable = [
        'url',
        'name',
        'order_component_id',
        'original_path',
        'initiator_type',
        'initiator_id',
    ];


    function initiator()
    {
        return $this->morphTo();
    }

    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class);
    }

    function owner()
    {
        return $this->morphTo();
    }

    function getUrlAttribute($val)
    {
        if($this->original_path) {

            return route('order_media', $this->id);
        }

        return $val;
    }
}
