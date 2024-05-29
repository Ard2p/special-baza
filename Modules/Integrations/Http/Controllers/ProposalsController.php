<?php

namespace Modules\Integrations\Http\Controllers;

use App\Machinery;
use App\Service\ProposalService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Integrations\Rules\Coordinates;
use Modules\Orders\Entities\Order;

class ProposalsController extends Controller
{


    function mapOrder(Order $order)
    {
        return [
            'id' => $order->id,
            'amount' => $order->amount / 100,
            'user_id' => $order->user_id,
            'date_from' => $order->date_from,
            'region_id' => $order->region_id,
            'shifts_count' => $order->days,
            'address' => $order->address,
            'date_to' => $order->date_to,
           // 'contractor_id' => $order->winner_offer->user_id ?? null,
            'vehicles' => $order->vehilces()->map(function ($machine) {

                return Machinery::integrationMap($machine);
            })
        ];
    }

    function mapProposal(Proposal $proposal)
    {
        return [
            'id' => $proposal->id,
            'sum' => $proposal->sum / 100,
            'user_id' => $proposal->user_id,
            'date' => $proposal->date,
            'region_id' => $proposal->region_id,
            'shifts_count' => $proposal->days,
            'address' => $proposal->address,
            'end_date' => $proposal->end_date,
            'vehicles' => $proposal->types->map(function ($type) {

                return [
                    'category_id' => $type->id,
                    'brand_id' => $type->pivot->brand_id,
                    'comment' => $type->pivot->comment,
                ];
            })
        ];
    }

    function getOrders(Request $request, $user_id, $id = null)
    {
        User::currentIntegration()->findOrFail($user_id);

        $proposals = Proposal::contractorOrders($user_id)->orderBy('created_at', 'DESC');

        $proposals = $id ? $proposals->findOrFail($id) : $proposals->get();

        return $id ? $this->mapOrder($proposals) : $proposals->map(function ($p) {
            return $this->mapOrder($p);
        });
    }


    function getAllOrders()
    {
        return Proposal::contractorIntegrationOrders()->get()->map(function ($p) {
            return $this->mapOrder($p);
        });
    }

    function addMachineryCoordinate(Request $request, $proposal_id, $machine_id)
    {
        $arr = ['status' => 'required|in:on_the_way,arrival,done'];

        if (request()->has('coordinates')) {
            $arr = array_merge($arr, [
                'coordinates' => new Coordinates
            ]);
        }

        $steps = [
            'on_the_way' => 1,
            'arrival' => 2,
            'done' => 3,
        ];

        $proposal = Proposal::checkAccepted()->findOrFail($proposal_id);

        Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();
        })->findOrFail($machine_id);

        $proposal->winner_offer->machines()->findOrFail($machine_id);

        $errors = Validator::make($request->all(), $arr)->errors()->getMessages();


        if ($errors) {

            return response()->json($errors, 400);
        }


        $result = $proposal->addMachineryCoordinates($machine_id, $steps[$request->status], $request->coordinates);

        if (!$result) {
            return response()->json(['status' => 'Данный статус уже отправлен указанной единице техники.'], 400);
        }

        return response()->json([]);
    }

    function refuse($user_id, $proposal_id)
    {
        $proposal = Proposal::checkAccepted()->whereHas('offers', function ($q) use ($user_id) {
            $q->where('is_win', 1)->whereHas('user', function ($q) use ($user_id) {
                $q->currentIntegration()->whereId($user_id);
            });

        })->findOrFail($proposal_id);

        DB::beginTransaction();

        $proposal->refuse('contractor');

        DB::commit();

        return response()->json();
    }


    function createProposal(Request $request, $user_id)
    {
        User::currentIntegration()->findOrFail($user_id);
        $service = new ProposalService($request);
        $errors = $service->validateRequest()->errors()->getMessages();
        if ($errors) {
            return response()->json($errors, 400);
        }

        $service = $service->forUser($request->user_id)->create();

        return $this->mapProposal($service->created_proposal);
    }

    function getProposals($user_id, $proposal_id = null)
    {
        //User::currentIntegration()->findOrFail($user_id);

        $proposal = Proposal::whereHas('user', function ($q) use ($user_id) {
            return $q->currentIntegration()->whereId($user_id);

        });


        return $proposal_id ? $this->mapProposal($proposal->findOrFail($proposal_id))
            : $proposal->get()->map(function ($proposal) {
                return $this->mapProposal($proposal);
            });
    }

    function refuseByCustomer($proposal_id, $user_id)
    {
        $proposal = Proposal::checkAvailable()->whereHas('user', function ($q) use ($user_id) {
            return $q->currentIntegration()->whereId($user_id);

        })->findOrFail($proposal_id);


        $proposal->refuse('customer');

        return response()->json();
    }
}
