<?php

namespace Modules\Orders\Entities;

use App\System\OrderableModel;
use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class OrderDocument extends Model implements Sortable
{
    use SortableTrait;

    protected $sortable = [
        'order_column_name' => 'order_column'
    ];
    protected $fillable = [
        'name',
        'url',
        'owner_type',
        'order_id',
        'type',
        'user_id',
        'order_column',
        'ext_type',
        'details',
        'dispatcher_invoice_id',
    ];

    protected $casts = [
        'owner_type' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'details' => 'object',
    ];

    const UPLOAD_DIR = 'uploads/documents/order';

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            $model->ext_type = last(explode('.', $model->url));
            return $model;
        });

        static::creating(function ($model) {
            $model->ext_type = last(explode('.', $model->url));
            return $model;
        });

        static::deleting(function ($model) {

            $model->deleteFile();
        });
    }

    public function newQuery()
    {
        return parent::newQuery();
    }

    function order()
    {
        return $this->morphTo('order');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function scopeCurrentUser($q)
    {
        return $q->where('user_id', Auth::id());
    }

    function deleteFile()
    {
        $path = str_replace(config('app.url'), '', $this->url);

        Storage::disk()->delete($path);
    }

    public function buildSortQuery()
    {
        return static::query()->where('order_id', $this->order_id)
            ->where('order_type', $this->order_type)
            ->where('ext_type', $this->ext_type);
    }

    public function invoice()
    {
        return $this->belongsTo(DispatcherInvoice::class,'dispatcher_invoice_id');
    }
}
