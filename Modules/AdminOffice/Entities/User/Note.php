<?php

namespace Modules\AdminOffice\Entities\User;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $table = 'user_notes';

    protected $fillable = [
        'text',
        'type',
        'attachments',
        'user_id',
        'manager_id'
    ];

    protected $casts = [
        'attachments' => 'array'
    ];

    protected $appends = ['manager_account'];

    const TYPE_CALL = 'call';
    const TYPE_MEETING = 'meeting';
    const TYPE_EMAIL = 'email';
    const TYPE_NOTES = 'notes';


    static function getTypes()
    {
        return [
             self::TYPE_CALL => 'Звонок',
            self::TYPE_MEETING => 'Встреча',
            self::TYPE_EMAIL => 'Эл. письмо',
            self::TYPE_NOTES  => 'Заметка',
        ];
    }

    function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function getManagerAccountAttribute()
    {
        return $this->manager ? "#{$this->manager->id} {$this->manager->email}" : '';
    }
}
