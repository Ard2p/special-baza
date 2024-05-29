<?php

namespace App\Service\Scoring\Models;

use App\User;
use App\Overrides\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\Customer;

/**
 * @property integer $type
 */
class Scoring extends Model
{

    const PHYSICAL = 1;
    const LEGAL = 2;
    const PHYSICALFSSP = 3;

    const DIRECT = 1;
    const CACHE = 2;

    const FINAL_RESULT_APPROVED = 0;
    const FINAL_RESULT_REJECTED = 1;
    const FINAL_RESULT_API_ERROR = 2;

    public static $legalRejectStatuses = [
        'InTerminationProcess', 'Terminated', 'Bankruptcy'
    ];

    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            $model->setFinalResult();
        });
    }

    protected $fillable = [
        'id',
        'type',
        'format',
        'found',
        'inn',
        'firstname',
        'lastname',
        'midname',
        'birthdate',
        'passport_number',
        'issue_date',
        'result_code',
        'result_message',
        'score',
        'description',
        'response_json',
        'company_branch_id',
        'creator_id',
        'final_result',
        'customer_id',
    ];

    protected $casts = [
        'response_json' => 'json',
        'found' => 'boolean'
    ];

    public function company_branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getTypeNameAttribute(): string
    {
        switch ($this->type) {
            case self::PHYSICAL:
                return 'Физическое лицо';
            case self::LEGAL:
                return 'Юридическое лицо';
            default:
                return '';
        }
    }

    public static function getTypes(): array
    {
        return [
            ['id' => self::PHYSICAL, 'name' => 'Физическое лицо'],
            ['id' => self::LEGAL, 'name' => 'Юридическое лицо'],
        ];
    }


    private function setFinalResult()
    {
        $finalResult = 0;

        if ($this->result_code != 0) {
            $finalResult = Scoring::FINAL_RESULT_API_ERROR;
        } elseif (($this->type !== self::LEGAL && $this->score < 100) || $this->hasDebts() || $this->hasDuplicatedManagers()) {
            $finalResult = Scoring::FINAL_RESULT_REJECTED;
        }
        $this->final_result = $finalResult;
        $this->save();
    }

    public function hasDebts()
    {
        if ($this->type == self::LEGAL) {
            $item = $this->getActiveItem();
            if (!$item) {
                return true;
            }
            return collect($item['proceedings']['Status'])
                    ->where('Value', 'Open')->sum('Sum') > 3000000;
        } elseif ($this->type == self::PHYSICALFSSP) {
            return collect($this->response_json['data'])->sum('sum') > 50000;
        }
        return 0;
    }

    public function hasDuplicatedManagers()
    {
        if ($this->type !== self::LEGAL) {
            return false;
        }
        if (strlen($this->inn) > 10) {
            return false;
        }
        $year = Carbon::now()->startOfYear();
        $item = $this->getActiveItem();
        if (!$item) {
            return true;
        }
        return collect($item['entity']['Managers']['Items'])
                ->filter(function ($item) use ($year) {
                    return (!empty($item['Date']) && Carbon::parse($item['Date'])->startOfYear()->eq($year));
                })
                ->count() > 1;
    }

    private function getActiveItem()
    {
        if (isset($this->response_json['TotalItems'])) {
            if ($this->response_json['TotalItems'] == 1) {
                return $this->response_json['Items'][0];
            } elseif ($this->response_json['TotalItems'] > 1) {
                foreach ($this->response_json['Items'] as $item) {
                    if (isset($item['entity']) && $item['entity']['Status']['Status'] == 'Active' && $item['Type'] !== 'Person') {
                        return $item;
                    }
                }
            }
        }
        return null;
    }
}
