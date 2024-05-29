<?php

namespace App\Widget;

use App\Overrides\Model;

class WidgetKeyHistory extends Model
{
    protected $fillable = ['old_key', 'widget_id'];
}
