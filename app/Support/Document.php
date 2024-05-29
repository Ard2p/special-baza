<?php

namespace App\Support;

use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{

    protected $fillable = [
        'user_id', 'type', 'number', 'date', 'body', 'url', 'author',
    ];

    public static $requiredFields = [
        'type' => 'required|integer',
        'number' => 'required|integer',
        'date' => 'required|date',
        'body' => 'required|string',
        'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
    ];

    protected $appends = ['account_type'];

    public function getAccountTypeAttribute()
    {
        switch ($this->billing_type){
            case 'customer':
                $type = 'Заказчик';
                break;
            case 'contractor':
                $type = 'Исполнитель';
                break;
            default:
                $type = 'Общий';
                break;
        }

        return $type;
    }

    function scopeCurrentUser($q)
    {
        return $q->where(function ($q){
            $q->where('user_id', Auth::user()->id);
            $q->orWhere('billing_type', 'all');
        });
    }

    function scopeCurrentAccount($q)
    {
        $acc_type = Auth::user()->getCurrentRoleName();
        return $q->whereIn('billing_type', [$acc_type, 'all']);
    }

    function _type()
    {
        return $this->hasOne('App\Support\DocumentType', 'id', 'type');
    }


    function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id')->withTrashed();;
    }

    function scopeWithFilters($q, $request)
    {

        if ($request->filled('date_from')) {

            $q->whereDate('created_at', '>=', Carbon::parse($request->input('date_from')));
        }

        if ($request->filled('date_to')) {

            $q->whereDate('created_at', '<=', Carbon::parse($request->input('date_to')));
        }

        if ($request->filled('type')) {

            $q->where('type', $request->input('type'));
        }

        return $q;
    }
}
