<?php

namespace App\Directories;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
  public $timestamps = false;

  protected $fillable = ['name', 'transaction_type'];

  static function getTypeLng($type)
  {
     return trans('transbaza_transaction_statuses.' . $type);
  }
}
