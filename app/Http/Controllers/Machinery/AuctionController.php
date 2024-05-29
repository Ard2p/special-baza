<?php

namespace App\Http\Controllers\Machinery;

use App\City;
use App\Events\AuctionOffer;
use App\Machinery;
use App\Machines\SaleOffer;
use App\Machines\Type;
use App\Modules\MachineAuctions\Auction;
use App\Support\Region;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class AuctionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $alias = null)
    {
        $machines = Machinery::with('auction')->whereHas('auction', function ($q){
            $q->whereModerated(1);
        })->get();

        if (!is_null($alias)) {
            $user = \App\User::with(['machines' => function ($q) use ($request) {
                if ($request->filled('type_id')) {
                    $q->whereType($request->type_id);
                }
                $q->whereHas('sale');
                if ($request->filled('region')) {
                    $q->whereRegionId($request->region);
                }
                if ($request->filled('city_id')) {
                    $q->whereCityId($request->city_id);
                }
            }])
                // ->whereContractorAliasEnable(1)
                ->whereContractorAlias($alias)->firstOrFail();


            $machines = $user->machines;
        } else {
            $user = null;
            $machines = Machinery::with('user');
            $machines->whereHas('auction', function ($q){
                $q->whereModerated(1);
            });
            if ($request->filled('type_id')) {
                $machines->whereType($request->type_id);
            }
            if ($request->filled('region')) {
                $machines->whereRegionId($request->region);
            }
            if ($request->filled('city_id')) {
                $machines->whereCityId($request->city_id);
            }

            $machines = $machines->paginate(20);
        }


        $regions = Region::whereCountry('russia')->whereHas('machines', function ($q) use ($user) {
            if ($user) {
                $q->whereUserId($user->id);
            }

        })->get();

        $types = Type::whereHas('machines', function ($q) use ($user) {
            if ($user) {
                $q->whereUserId($user->id);
            }
        })->get();

        $initial_type = $request->filled('type_id') ? Type::find($request->type_id) : '';
        $initial_region = ($request->filled('region') ? Region::find($request->region) : '');
        $checked_city_source = ($request->filled('city_id') ? City::find($request->city_id) : '');

        return view('user.auction.index', compact('machines', 'user', 'types', 'regions', 'initial_region', 'initial_type', 'checked_city_source'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auction = Auction::findOrFail($id);


        if(!$auction->moderated){
            return view('moderate_error');
        }
        $locale = Lang::get('transbaza_auctions');

        $machine = $auction->machine;
        return view('user.auction.show', compact('auction', 'machine', 'locale'));
    }

    function here($id)
    {
        $auction = Auction::with('offers')->whereModerated(1)->findOrFail($id);
        if (\Auth::check()) {

            AuctionOffer::dispatch($auction);

        }
        return response()->json($auction->toArray());
    }

    function addBid(Request $request, $id)
    {
        \DB::beginTransaction();
        $auction = Auction::with('offers')->whereModerated(1)->findOrFail($id);
        if (!\Auth::check()) {
            return response()->json(['auth_error' => [view('includes.auth_error',
                [
                    'message' => trans('transbaza_auctions.validate_no_auth'),
                    'url' => route('login', ['redirect_back' => route('auctions.show', $id)]),
                ])->render()]], 419);
        }


        $validator = Validator::make($request->all(), [
            'bid' => 'required|numeric|min:0',
        ]);

        $bid = sumToPenny($request->bid);

        $errors = $validator
            ->errors()
            ->getMessages();

        if ($bid < $auction->min_bid) {
            $errors['bid'] = ["Сумма меньше допустимой!"];
        }
        if ($auction->is_close) {
            $errors['modals'] = [ trans('transbaza_auctions.validate_end_auction')];
        } elseif ($auction->last_user_id === \Auth::id()) {
            $errors['modals'] = [ trans('transbaza_auctions.validate_last_bid')];
        }

        if ($errors) return response()->json($errors, 419);

        \App\Modules\MachineAuctions\AuctionOffer::create([
            'auction_id' => $auction->id,
            'user_id' => \Auth::id(),
            'sum' => $request->bid,
        ]);
        \DB::commit();
        $auction->refresh();

        AuctionOffer::dispatch($auction);

        return response()->json(['message' =>  trans('transbaza_auctions.validate_add_bid')]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
