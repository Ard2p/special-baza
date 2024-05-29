<?php

namespace Modules\CompanyOffice\Entities\Employees;

use App\User;
use App\Overrides\Model;

class EmployeeTaskOperation extends Model
{

    protected $fillable = [
        'employee_task_id',
        'old_status',
        'new_status',
        'created_by_id',

    ];

    function employee_task()
    {
        return $this->belongsTo(EmployeeTask::class);
    }

    function created_by()
    {
        return $this->belongsTo(User::class, 'bind', 'employee_tasks_binds');
    }
}
