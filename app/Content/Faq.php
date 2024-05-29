<?php

namespace App\Content;

use App\Overrides\Model;
use App\System\OrderableModel;

class Faq extends Model
{
    use OrderableModel;

    public function getOrderField()
    {
        return 'order';
    }
}
