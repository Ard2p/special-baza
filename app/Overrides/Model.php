<?php

namespace App\Overrides;


use App\Service\RequestBranch;
use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class Model extends \Illuminate\Database\Eloquent\Model
{
    protected $dateFormat = 'Y-m-d H:i:s';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date instanceof \DateTimeImmutable ?
            CarbonImmutable::instance($date)->toDateTimeString() :
            Carbon::instance($date)->toDateTimeString();
    }

   public function fromDateTime($value)
   {
       /** @var Carbon $date */
       $date = parent::fromDateTime($value);

       try {
           /** @var RequestBranch $branch */
           $branch = app(RequestBranch::class);
       }catch (\Exception $exception) {
           $branch = (object) ['companyBranch' => $this->company_branch];
       }
       if(!$branch->companyBranch) {
           return  $date;
       }
       return $date ? Carbon::parse($date, $branch->companyBranch?->timezone ?:'Europe/Moscow' ): false;
   }

    public function freshTimestamp()
    {
        try {
            /** @var RequestBranch $branch */
            $branch = app(RequestBranch::class);
        }catch (\Exception $exception) {
            $branch = (object) ['companyBranch' => $this->company_branch];
        }
        if(!$branch->companyBranch) {
            return  now();
        }
        return Carbon::now($branch->companyBranch->timezone ? CarbonTimeZone::create($branch->companyBranch->timezone) : 'Europe/Moscow');
    }


}
