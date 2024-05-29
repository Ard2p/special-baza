<?php

namespace App\Http\Controllers\Avito\Models;

use App\Overrides\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Orders\Entities\Order;

class AvitoHoldsHistory extends Model
{


    public const TYPE_IN = 1;
    public const TYPE_OUT = 2;

    protected $fillable = [
        'avito_order_id',
        'old_order_id',
        'new_order_id',
        'hold',
        'type',
    ];

    public function avito_order(): BelongsTo
    {
        return $this->belongsTo(AvitoOrder::class);
    }

    public function old_order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'old_order_id');
    }

    public function new_order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'new_order_id');
    }
}
