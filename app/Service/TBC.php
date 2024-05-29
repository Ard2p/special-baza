<?php

namespace App\Service;

use App\Marketing\EmailLink;
use App\Marketing\SmsLink;
use App\User;
use Illuminate\Support\Facades\Auth;

class TBC
{

    function incrementHistory(User $user, $sum, $linkInstance = null, $admin = null, $reason = '')
    {
        $new_sum = $user->getBalance('tbc');
        $properties = [
            'user_id' => $user->id,
            'old_sum' => $new_sum - $sum,
            'new_sum' => $new_sum,
            'type' => 'increment',
            'sum' => $sum,
        ];
        $properties = $this->modifyProperties($properties, $linkInstance, $admin, $reason);

       return User\TbcBalanceHistory::create($properties);
    }

    function decrementHistory(User $user, $sum, $linkInstance = null, $admin = null, $reason = '')
    {
        $new_sum = $user->getBalance('tbc');
        $properties = [
            'user_id' => $user->id,
            'old_sum' => $new_sum + $sum,
            'new_sum' => $new_sum,
            'type' => 'decrement',
            'sum' => $sum,
        ];
        $properties = $this->modifyProperties($properties, $linkInstance, $admin, $reason);
        return User\TbcBalanceHistory::create($properties);
    }

    private function modifyProperties($properties, $linkInstance, $admin, $reason)
    {
        if ($admin) {
            $properties['admin_id'] = Auth::id();
            $properties['reason'] = $reason;
        }
        if (!is_null($linkInstance)) {
            if ($linkInstance instanceof EmailLink) {
                $properties['email_link_id'] = $linkInstance->id;

            } elseif ($linkInstance instanceof SmsLink) {
                $properties['sms_link_id'] = $linkInstance->id;
            }
        }

        return $properties;
    }


}