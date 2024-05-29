<?php

namespace Modules\CompanyOffice\Http\Controllers\Crm;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Services\CustomerService;
use Modules\Orders\Entities\Order;

class MailingsController extends Controller
{
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;

        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'show',
                'getEmails',
            ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_UPDATE)->only(
            [
                'store',
                'update',
                'start',
                'destroy',
                'deleteEmails',
                'sendEmails',

            ]);

    }

    function start($id)
    {
        if (!$this->currentBranch->mailConnector) {
            return response()->json([]);
        }

       $response = $this->currentBranch->mailConnector->startMailing( $id);

        return response()->json($response, $this->currentBranch->mailConnector->lastStatusCode);
    }

    function getEmails(Request $request, $id)
    {
        if (!$this->currentBranch->mailConnector) {
            return response()->json([]);
        }
        $emails = $this->currentBranch->mailConnector->getMailingEmails($request->all(), $id);

        return response()->json($emails, $this->currentBranch->mailConnector->lastStatusCode);
    }

    function deleteEmails(Request $request, $id)
    {
        $request->validate([
            'emails' => 'required|array'
        ]);

        if (!$this->currentBranch->mailConnector) {
            return response()->json([]);
        }
        $response = $this->currentBranch->mailConnector->removeEmailsFromMailing($request->all(), $id);

        return response()->json($response, $this->currentBranch->mailConnector->lastStatusCode);
    }

    function cloneMailing(Request $request, $uuid)
    {
        if (!$this->currentBranch->mailConnector) {
            return response()->json([]);
        }
        $this->currentBranch->mailConnector->cloneMailing($uuid);

        return response()->json([], $this->currentBranch->mailConnector->lastStatusCode);
    }

    function sendEmails(Request $request, $id)
    {
        $request->validate([
            'send_type' => 'required|in:filter,selected,no_orders',
            'selected' => ($request->input('send_type') === 'selected' ? 'required' : 'nullable') . '|array',
        ]);
        if (!$this->currentBranch->mailConnector) {
            return response()->json([]);
        }
        $customers = Customer::query()->forBranch();

        switch ($request->input('send_type')) {
            case 'filter':
                $filter = new Filter($customers);
                $filter->getLike([
                    'company_name' => 'company_name',
                ]);
                CustomerService::filter($customers, $request);
                break;
            case 'selected':
                $customers->whereIn('id', $request->input('selected'));
                break;
            case 'no_orders':
                $customers->whereDoesntHave('orders', function ($query){
                    return $query->where('status', Order::STATUS_ACCEPT);
                });
                break;
        }

        $emails = collect();
        $customers = $customers->get();

        $customers->each(function ($customer) use ($emails) {

            $current = $customer->toArray();
            $current['position'] =  trans('calls/calls.main');
            $current['contact_person'] = $customer->contact_person;
            if($customer->email && filter_var(strtolower($customer->email), FILTER_VALIDATE_EMAIL)) {
                $emails->push([

                    'email' => strtolower($customer->email),
                    'raw_data' => $current,
                ]);

            }

            foreach ($customer->contacts as $contact) {
                foreach ($contact->emails as $email) {
                    if($email->email && filter_var(strtolower($email->email), FILTER_VALIDATE_EMAIL)) {
                        $current['position'] = $contact->position;
                        $current['contact_person'] = $contact->full_name;
                        $emails->push([
                            'email' => strtolower($email->email),
                            'raw_data' => $current,
                        ]);
                    }

                }

            }
        });
        $emails =  $emails->unique('email');
        $request->merge(['emails' => $emails]);
        $response = $this->currentBranch->mailConnector->addEmailsToMailing($request->all(), $id);

        return response()->json($response, $this->currentBranch->mailConnector->lastStatusCode);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        if (!$this->currentBranch->mailConnector) {
            return response()->json([]);
        }
        $mailings = $this->currentBranch->mailConnector->getMailings($request->all());

        return response()->json($mailings, $this->currentBranch->mailConnector->lastStatusCode);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        if (!$this->currentBranch->mailConnector) {
            return response()->json([]);
        }
        $mailing = $this->currentBranch->mailConnector->addMailing($request->all());

        return response()->json($mailing, $this->currentBranch->mailConnector->lastStatusCode);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request, $id)
    {
        if (!$this->currentBranch->mailConnector) {
            response()->json([]);
        }
        $mailing = $this->currentBranch->mailConnector->getMailings($request->all(), $id);

        return response()->json($mailing, $this->currentBranch->mailConnector->lastStatusCode);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (!$this->currentBranch->mailConnector) {
            response()->json([]);
        }
        $mailing = $this->currentBranch->mailConnector->updateMailing($request->all(), $id);

        return response()->json($mailing, $this->currentBranch->mailConnector->lastStatusCode);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
