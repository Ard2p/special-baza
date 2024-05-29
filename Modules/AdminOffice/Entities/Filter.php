<?php

namespace Modules\AdminOffice\Entities;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Filter
{

    private $likeKeys = [];

    private $equalKeys = [];

    /** @var Builder $query */
    private $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    function getEqual(
        $keys,
        $boolean = false)
    {
        foreach ($keys as $request_key => $db_key) {

            if (request()->filled($request_key)) {
                $value =
                    $boolean
                        ? toBool(request()->input($request_key))
                        : request()->input($request_key);
                $this->query->where($db_key, $value);
            }
        }

        return $this;
    }


    function getLike($keys)
    {
        foreach ($keys as $request_key => $db_key) {
            if (request()->filled($request_key)) {
                $input = request()->input($request_key);
                $this->query->where($db_key, 'like', "%{$input}%");
            }
        }

        return $this;
    }

    function getLikeMultiple($keys)
    {

        $this->query->where(function ($q) use ($keys) {
            foreach ($keys as $request_key => $db_key) {

                if(is_numeric($request_key))
                    $request_key = $db_key;

                $input = request()->input($request_key);
                if (request()->filled($request_key)) {
                    $q->orWhere($db_key, 'like', "%{$input}%");
                }
            }
        });

        return $this;
    }


    function getBetween(
        $keys,
        $toPeny = false)
    {
        $i = 0;
        foreach ($keys as $request_key => $db_key) {
            if (request()->filled($request_key)) {
                $input = $toPeny
                    ? ((float)request()->input($request_key)) * 100
                    : request()->input($request_key);

                $this->query->where($db_key, ($i === 0
                    ? '>='
                    : '<='), $input);
            }
            ++$i;
        }

        return $this;
    }

    function getDateBetween($keys)
    {
        $i = 0;
        foreach ($keys as $request_key => $db_key) {
            if (request()->filled($request_key)) {
                $input = Carbon::parse(request()->input($request_key));

                $this->query->whereDate($db_key, ($i === 0
                    ? '>='
                    : '<='), (string)$input);
            }
            ++$i;
        }
        return $this;
    }

    function inArray($keys)
    {
        foreach ($keys as $request_key => $db_key) {

            if (request()->filled($request_key)) {
                $input = request()->input($request_key, []);
                $data =
                    is_array($input)
                        ?: @json_decode($input, true);
                if ($data) {
                    $this->query->whereIn($db_key, array_values($data));
                }

            }


        }

        return $this;
    }


}
