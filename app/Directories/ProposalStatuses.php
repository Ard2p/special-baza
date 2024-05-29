<?php

namespace App\Directories;

use Illuminate\Database\Eloquent\Model;

class ProposalStatuses extends Model
{
   public $timestamps = false;

   protected $fillable = ['name', 'proposal_status_id'];
}
