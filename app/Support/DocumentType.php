<?php

namespace App\Support;

use App\Overrides\Model;

class DocumentType extends Model
{
  public  $timestamps = false;
  protected $fillable = ['name'];
}
