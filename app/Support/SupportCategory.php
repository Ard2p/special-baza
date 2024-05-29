<?php

namespace App\Support;

use App\Overrides\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportCategory extends Model
{
   use SoftDeletes;
}
