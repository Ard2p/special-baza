<?php

namespace App\Overrides;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;

class ModelFilter extends \EloquentFilter\ModelFilter
{
    use Macroable {
        Macroable::__call as private __macroCall;
    }

    public array $equalsColumns = [];
    public array $likeColumns = [];

    function __call($method, $args)
    {

        if (! static::hasMacro($method)) {
            return parent::__call($method, $args);
        }

        return $this->__macroCall($method, $args);
    }

    public function methodIsCallable($method)
    {
        return (! $this->methodIsBlacklisted($method) &&
            method_exists($this, $method) &&
            ! method_exists(\EloquentFilter\ModelFilter::class, $method) || static::hasMacro($method));
    }

    public function filterInput()
    {
        parent::filterInput();

        foreach ($this->equalsColumns as $requestKey => $column) {

            if(is_int($requestKey)) {
                $requestKey = $column;
            }
            $inputValue = Arr::wrap($this->input($requestKey));
            if(empty($inputValue)) {
                continue;
            }

            $this->whereIn($column, $inputValue);
        }

        foreach ($this->likeColumns as $requestKey => $column) {
            if(is_int($requestKey)) {
                $requestKey = $column;
            }
            $inputValue = $this->input($requestKey);
            if(empty($inputValue)) {
                continue;
            }
            $this->where($column, 'like', "%{$inputValue}%");
        }
    }

    function jsonLike($column, $property, $value)
    {
        return $this->havingRaw("(CASE WHEN JSON_VALID({$column}) THEN JSON_VALUE({$column}, '$.{$property}') like ?  ELSE '0' END)", ["%{$value}%"]);
    }

    function id($val)
    {
        $id = is_array($val) ? $val : [$val];

        return $this->whereIn('id', $id);
    }

}
