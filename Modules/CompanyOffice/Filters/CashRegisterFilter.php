<?php

namespace Modules\CompanyOffice\Filters;

use App\Overrides\ModelFilter;
use App\Traits\Sortable;
use Carbon\Carbon;

class CashRegisterFilter extends ModelFilter
{
    use Sortable;

    function dateFrom($val)
    {
        return $this->where('created_at', '>=', Carbon::parse($val)->setTimezone(config('app.timezone'))->startOfDay());
    }

    function dateTo($val)
    {
        return $this->where('created_at', '<=', Carbon::parse($val)->setTimezone(config('app.timezone'))->endOfDay());
    }

    function machineryBase($id)
    {
        return $this->where('machinery_base_id', $id);
    }

    function expenditure($id)
    {
        return match ($id) {
            'withdrawal' => $this->where('ref', 'like', '%withdrawal%'),
            'card_pay' => $this->where('ref', 'like', '%card%'),
            default => $this->where('expenditure_id', $id),
        };
    }

}