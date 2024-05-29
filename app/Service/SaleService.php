<?php

namespace App\Service;

use App\Ads\Advert;
use App\City;
use App\Directories\TransactionType;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\Sale;
use App\Machines\Type;
use App\Marketing\EmailLink;
use App\Marketing\SmsLink;
use App\Option;
use App\Role;
use App\Support\Gmap;
use App\Support\Region;
use App\User;
use App\User\BalanceHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SaleService
{

    private $sale_price, $advert_price, $auction_price, $description, $machine, $advert_url, $auction_url;

    function __construct($sale_price, $advert_price, $auction_price, $description, Machinery $machine)
    {

        $this->sale_price = $sale_price;
        $this->advert_price = $advert_price;
        $this->auction_price = $auction_price;
        $this->description = $description;
        $this->machine = $machine;

    }

    function publish_sale()
    {
        if (!$this->machine->sale) {
            Sale::create([
                'machinery_id' => $this->machine->id,
                'price' => $this->sale_price,
                'spot_price' => $this->sale_price,
                'description' => $this->description,
                'moderated' => Auth::user()->hasModerate('sales') ? 1 : 0
            ]);
        } else {
            $this->machine->sale->update([
                'price' => $this->sale_price,
                'spot_price' => $this->sale_price,
                'description' => $this->description,
            ]);
        }

        return $this;
    }

    function publishAdvertSale()
    {
        $name = "Продам {$this->machine->type_name}, {$this->machine->brand->name}";
        if (!$this->machine->advert) {
            $advert = Advert::create([
                'name' => $name,
                'alias' => $name,
                'region_id' => $this->machine->region->id,
                'category_id' => 2,
                'city_id' => $this->machine->city->id,
                'address' => $this->machine->address,
                'description' => $this->description,
                'reward_id' => 1,
                'reward_text' => null,
                'actual_date' => now()->addMonth(),
                'photo' => $this->machine->photo,
                'sum' => $this->advert_price,
                'user_id' => Auth::id(),
                'moderated' => Auth::user()->hasModerate('adverts') ? 1 : 0
            ]);
            $this->machine->update([
                'advert_id' => $advert->id
            ]);
        } else {
            $advert = $this->machine->advert;
        }
        $this->advert_url = $advert->url;
    }

    function getAdvertUrl()
    {
        return $this->advert_url;
    }
    function getActiontUrl()
    {
        return $this->auction_url;
    }
    function publishAuction()
    {
        $auction = !$this->machine->auction ? Auction::create([
            'machinery_id' => $this->machine->id,
            'type' => request()->input('auction_type'),
            'start_sum' => $this->auction_price,
            'actual_date' => now()->addMonth(),
            'description' => $this->description,
            'moderated' => Auth::user()->hasModerate('auctions') ? 1 : 0
        ]) : $this->machine->auction;

        $this->auction_url = $auction->url;

        return $this;
    }
}