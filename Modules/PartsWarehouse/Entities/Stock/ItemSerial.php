<?php

namespace Modules\PartsWarehouse\Entities\Stock;

use Illuminate\Database\Eloquent\Model;

class ItemSerial extends Model
{

    public $timestamps = false;

    protected $table = 'stock_item_serials';

    protected $fillable = [
        'serial',
        'item_id',
        'item_sale_id',
    ];

    function item()
    {
        return $this->belongsTo(Item::class);
    }

    function saleItem ()
    {
        return $this->belongsTo(self::class, 'item_sale_id');
    }

    function postingItem()
    {
        return $this->hasOne(self::class, 'item_sale_id');
    }
}
