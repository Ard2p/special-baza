<?php

namespace Modules\Orders\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerFeedback extends Model
{
    protected $fillable = [
        'content',
        'order_id'
    ];

    protected $table = 'customer_feedback';
}
