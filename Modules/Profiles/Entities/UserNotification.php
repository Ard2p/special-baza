<?php

namespace Modules\Profiles\Entities;

use App\User;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;


class UserNotification extends Model
{

    use BelongsToCompanyBranch;

    protected $fillable = [
        'type',
        'message',
        'link',
        'is_read',
        'user_id',
        'company_branch_id',
    ];

    const TYPE_ERROR = 'error';
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';

    protected $casts = [
        'is_read' => 'boolean'
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    static function getTypes()
    {
        return [
            self::TYPE_INFO,
            self::TYPE_ERROR,
            self::TYPE_SUCCESS
        ];
    }

    static function addNotification($message, User $user, $type = self::TYPE_INFO, CompanyBranch $companyBranch = null, $link = null)
    {
        if(!in_array($type, self::getTypes())) {
            throw new \InvalidArgumentException();
        }
        $item = new self([
            'type' => $type,
            'message' => $message,
            'user_id' => $user->id,
            'link' => $link,
            'company_branch_id' => ($companyBranch ? $companyBranch->id : null)
        ]);

        $item->save();

        return $item;
    }
}
