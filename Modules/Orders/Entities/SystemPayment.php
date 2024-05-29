<?php

namespace Modules\Orders\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemPayment extends Model
{
    protected $fillable = [
        'sum',
        'payment_uuid',
        'type',
        'status',
        'details',
        'user_id',
    ];

    protected $casts = [
        'sum' => 'int',
        'details' => 'object'
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
