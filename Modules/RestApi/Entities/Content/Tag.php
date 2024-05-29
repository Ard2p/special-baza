<?php

namespace Modules\RestApi\Entities\Content;

use App\Article;
use App\Machines\Type;
use App\Overrides\Model;

class Tag extends Model
{

    protected $fillable = ['name'];

    function articles()
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }

    public function categories()
    {
        return $this->morphedByMany(Type::class, 'taggable');
    }

    static function createOrGet($array_names)
    {
        $collection = collect([]);
        foreach ($array_names as $name) {

            $collection->push(self::firstOrCreate(['name' => mb_ucfirst(mb_strtolower($name))]));

        }

        return $collection;
    }

}
