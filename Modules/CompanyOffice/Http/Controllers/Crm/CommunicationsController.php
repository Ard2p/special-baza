<?php

namespace Modules\CompanyOffice\Http\Controllers\Crm;

use App\Helpers\RequestHelper;
use App\Service\RequestBranch;
use App\System\SpamEmail;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Transformers\Crm\CommunicationHistoryCollection;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Integrations\Entities\Telpehony\SpamPhone;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\Orders\Entities\Order;

class CommunicationsController extends Controller
{

    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->input('phone'))
            ]);
        }

        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'clip',
                'getEmployeeContacts'
            ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_UPDATE)->only(
            [
                'setStatus',
                'actionCalls',
                'actionMails',

            ]);

    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        /** @var Builder $calls */
        $calls = TelephonyCallHistory::query()->forCompany();

        $filter = new Filter($calls);
        $filter->getLike([
            'phone' => 'phone',
        ])->getDateBetween([
            'date_from' => 'created_at',
            'date_to' => 'created_at',
        ]);
        /* ->getEqual([
             'important' => 'important',
             'listened' => 'listened',
             'is_hidden' => 'is_hidden',
         ], true);*/

        if ($request->filled('id')) {
            $calls->where('id', $request->input('id'));
        }

        if (!Str::contains($request->input('marks'), 'is_hidden')) {
            $calls->where('is_hidden', false);
        }
        foreach (explode(',', $request->input('marks', '')) as $item) {
            switch ($item) {
                case 'important':
                case 'listened':
                case 'is_hidden':
                    $calls->where($item, true);
                    break;
                case 'unprocessed':
                    $calls->unprocessed();
                    $calls->incoming();
                    break;
                case 'new':
                    $calls->newCustomer();
                    break;
                case 'exists':
                    $calls->customerExists();
                    break;
            }
        }
        if ($request->filled('spam')) {
            $calls->spam();
        } else {
            $calls->noSpam();
        }


        if ($request->filled('exclude_contacts')) {
            $contacts = $request->input('exclude_contacts');
            if($this->currentBranch->sipuniTelephony) {
                $contacts = array_merge($contacts, collect($this->currentBranch->sipuniTelephony)->values()->all());
            }
            if($this->currentBranch->mangoTelephony) {
                $contacts = array_merge($contacts, collect($this->currentBranch->mangoTelephony)->values()->all());
            }
            $calls->whereNotIn('phone', $contacts);
        }

        if ($request->filled('contacts')) {

            $contacts = $request->input('contacts');

            $calls->whereIn('phone', $contacts);
        }

        if ($request->filled('communication')) {
            switch ($request->input('communication')) {
                case  'new':
                    $calls->whereNull('bind_id');
                    break;
                case  'prelead':
                    $calls->whereHasMorph('bind', [PreLead::class], function ($q) {
                        $q->where('dispatcher_pre_leads.status', PreLead::STATUS_OPEN);
                    });
                    break;
                case  'open':
                    $calls->whereHasMorph('bind', [Lead::class], function ($q) {
                        $q->where('dispatcher_leads.status', Lead::STATUS_OPEN);
                    });
                    break;
                case  'accept':
                    $calls->where(function (Builder $q) {
                        $q->whereHasMorph('bind', [Lead::class], function ($q) {
                            $q->where('dispatcher_leads.status', Lead::STATUS_ACCEPT);
                        });
                        $q->orWhereHasMorph('bind', [Order::class], function ($q) {
                            $q->where('orders.status', Order::STATUS_ACCEPT);
                        });
                    });

                    break;
                case  'done':

                    $calls->where(function (Builder $q) {
                        $q->whereHasMorph('bind', [Lead::class], function ($q) {
                            $q->whereIn('dispatcher_leads.status', [Lead::STATUS_DONE, Lead::STATUS_EXPIRED, Lead:: STATUS_CLOSE]);
                        });
                        $q->orWhereHasMorph('bind', [Order::class], function ($q) {
                            $q->where('orders.status', Order::STATUS_DONE);
                        });
                    });
                    break;
                case  'reject':
                    $calls->where(function (Builder $q) {
                        $q->whereHasMorph('bind', [PreLead::class], function ($q) {
                            $q->where('dispatcher_pre_leads.status', PreLead::STATUS_REJECT);
                        });
                        $q->orWhereHasMorph('bind', [Lead::class], function ($q) {
                            $q->whereNotNull('dispatcher_leads.reject_type');
                        });
                    });

                    break;
            }
        }

        if ($request->anyFilled('order_id', 'lead_id')) {
            $calls->where('bind_type',
                $request->filled('order_id')
                    ? Order::class
                    : Lead::class
            );
            $calls->where('bind_id', $request->input('order_id') ?: $request->input('lead_id'));
        }
        if ($request->filled('customer_id')) {
            $customer = Customer::query()->forBranch()->findOrFail($request->input('customer_id'));
            $calls->forCustomer($customer);
        }

        if ($request->filled('manager_id')) {
            $manager = User::query()->whereHas('branches', function ($q) {
                $q->where('company_branches.id', $this->currentBranch->id);
            })->findOrFail($request->input('manager_id'));
            $calls->forManager($manager);
        }

        if ($request->filled('type')) {
            $types = explode(',', $request->input('type'));
            $first = $types[0];
            $second = !empty($types[1]) ? $types[1] : null;
            $calls->where(function ($q) use ($first, $second) {

                $q->where('raw_data', 'like', "%\"{$first}\"%");
                if ($second) {
                    $q->orWhere('raw_data', 'like', "%\"{$second}\"%");
                }
            });

        }
        if ($request->filled('contact_person')) {

            $phones = collect();

            $contacts = Contact::query()
                ->forBranch()
                ->findByContactPerson($request->input('contact_person'))
                ->get();

            $customers = Customer::query()->forBranch()->where('contact_person', 'like', "%{$request->input('contact_person')}%")->pluck('phone');
            $phones = $phones->merge($customers);
            $contacts->each(function ($contact) use ($phones) {
                foreach ($contact->phones as $phone) {
                    $phones->push($phone);
                }

                if ($contact->owner instanceof Customer) {

                    $phones->push($contact->owner->phone);
                }
            });


            $calls->whereIn('phone', $phones->unique()->toArray());
        }

        if ($request->filled('sortBy')) {
            $desc = toBool($request->input('sortDesc'));
            switch ($request->input('sortBy')) {
                case 'date':
                    $calls->orderBy('created_at', $desc ? 'desc' : 'asc');
                    break;
                case 'status':
                    $calls->orderBy('listened', $desc ? 'desc' : 'asc');
                    break;
                case 'important':
                    $calls->orderBy('important', $desc ? 'desc' : 'asc');
                    break;

            }
        } else {
            $calls->orderBy('created_at', 'desc');
        }

        return CommunicationHistoryCollection::collection($calls->paginate($request->per_page ?: 10));
    }

    function clip(Request $request)
    {
        $rules = [
            'entity_type' => 'required|in:order,lead,prelead',
            'entity_id' => 'required|integer',

        ];
        if ($request->input('type') === 'call') {
            $rules['call_id'] = 'required|integer';
        }
        if ($request->input('type') === 'email') {
            $rules['email_uuid'] = 'required|string';
        }
        $request->validate($rules);

        switch ($request->input('entity_type')) {
            case 'lead':
                $item =Lead::query()->forBranch()->findOrFail($request->input('entity_id'));

                break;
            case 'order':

                $item =   Order::query()->contractorOrCustomer()->findOrFail($request->input('entity_id'));
                break;
            case 'prelead':
                $item =   PreLead::query()->forBranch()->findOrFail($request->input('entity_id'));
                break;
        }

        if ($request->input('type') === 'call') {
            $call = TelephonyCallHistory::query()->forCompany()->findOrFail($request->input('call_id'));

            $call->bind()->associate($item);

            $call->save();
        }
        if ($request->input('type') === 'email') {

            $this->currentBranch->mailConnector->bindMail($item, $request->input('email_uuid'));
        }

        return response()->json();
    }

    function setStatus(Request $request, $id)
    {
        $request->validate([
            'entity_type' => 'required|in:email,call',
            'type' => 'required|in:listened,important,is_read,force_inbox,is_hidden,favorites,delete',
            'condition' => 'required|boolean'
        ]);

        if ($request->input('entity_type') === 'call') {
            $call = TelephonyCallHistory::query()->forCompany()->findOrFail($id);

            $call->update([
                $request->input('type') => toBool($request->input('condition'))
            ]);
        }
        if ($request->input('entity_type') === 'email') {
            $this->currentBranch->mailConnector->setStatus($request->input('type'), $request->input('condition'), $id);
        }
        return response()->json();
    }

    function getMails(Request $request)
    {
        logger()->debug('Start mails');

        $connector = $request->input('owner') === 'user' ? \Auth::user()->mailConnector : $this->currentBranch->mailConnector;
        if ($connector) {

            if ($request->filled('customer_id')) {
                $customer = Customer::query()->forBranch()->findOrFail($request->input('customer_id'));
                $available_emails = $customer->contacts->pluck('email');
                $available_emails[] = $customer->email;
            }
            if ($request->filled('contact_person')) {
                $emails = collect();

                $contacts = Contact::query()
                    ->forBranch()
                    ->findByContactPerson($request->input('contact_person'))
                    ->get();

                $customers = Customer::query()->with('contacts')->forBranch()->where('contact_person', 'like', "%{$request->input('contact_person')}%")->pluck('email');
                $emails = $emails->merge($customers);
                $contacts->each(function ($contact) use ($emails) {
                    $emails->push($contact->email);
                    if ($contact->owner instanceof Customer) {

                        $emails->push($contact->owner->email);
                    }
                });

                $request->merge([
                    'customer_emails' => $emails->toArray()
                ]);

            }
            return $connector->getMails($request->all());
        }

        return response()->json([]);

    }

    function sendMail(Request $request)
    {
        $request->validate([
            'owner' => 'required|in:user,company',
            'emails' => 'required|array',
            'emails.*' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:65000',
            'attachments' => 'nullable|array',
            'reply_to' => 'nullable|string',
        ]);
        $connector = $request->input('owner') === 'company' ? $this->currentBranch->mailConnector : \Auth::user()->mailConnector;

        if(!$connector)
            return response()->json([], 400);

        $attachments = [];
        foreach ($request->input('attachments') as $i => $file) {
            $url = Storage::disk()->url($file);
            $parts = explode('/', $url);
            $encoded = array_pop($parts);
            $name = $encoded;

            $parts[] = $encoded;
            $attachments[] = [
                'path' =>  implode('/', $parts),
                'name' => $name
            ];
        }
        $result = $connector->sendRawEmail($request->input('emails'), $request->input('subject'), $request->input('body'), $attachments, $request->input('reply_to'));
        if ($connector->lastStatusCode === 200)
            return response()->json();
        else
            return response()->json($result, 400);
    }


    function actionCalls(Request $request)
    {
        $request->validate([
            'action' => 'required|in:add_spam,remove_spam',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
        ]);
        switch ($request->input('action')) {
            case 'add_spam':
                SpamPhone::create([
                    'phone' => $request->input('phone'),
                    'company_id' => $this->currentBranch->company_id
                ]);
                break;
            case 'remove_spam':
                SpamPhone::query()->forCompany()->where('phone', $request->input('phone'))->delete();
                break;
        }

        return response()->json();
    }

    function actionMails(Request $request)
    {

        $request->validate([
            'type' => 'required|in:spam,email',
            'action' => 'required|in:add,remove,unclip',
            'email' => 'nullable|email'
        ]);

        if ($this->currentBranch->mailConnector) {
            if($request->input('type') === 'email') {
                $this->currentBranch->mailConnector->unclip($request->input('id'));
            }else {
                $this->currentBranch->mailConnector->toSpam($request->input('email'), $request->input('action'));
            }

        }

        return response()->json();
    }

    function getEmployeeContacts()
    {
//        $contacts = Contact::query()->where(function (Builder $q) {
//            $q->whereHasMorph('owner', [CompanyWorker::class])
//                ->orWhereHasMorph('owner', [CompanyBranch::class]);
//        })->forBranch();

        return CompanyWorker::query()->forBranch()->with('contacts')->get();
    }

    function personsList(Request $request)
    {
        /** @var Builder $query */
        $query = User\IndividualRequisite::query()->forBranch()
            ->with('phones', 'emails', 'principals', 'contactOwners')
            ->where('type', User\IndividualRequisite::TYPE_PERSON)
            ->orderBy('id', 'desc');

        if($request->filled('customer_id')){
            $query->whereHas('contactOwners', fn (Builder $q) => $q->where($q->qualifyColumn('id'), $request->input('customer_id')));
        }
        if($request->filled('name')){
            $query->addSelect(\DB::raw("*, CONCAT(firstname, ' ', middlename, ' ', surname) as full"));
            $query->having('full', 'like', "%{$request->input('name')}%");
        }
        if($request->boolean('principal')){
            $query->whereRelation('principals', 'end_date', '>=', now()->format('Y-m-d'));
        }
        if($request->filled('phone')) {
            $phone = trimPhone($request->input('phone'));
            $query->whereHas('phones', fn(Builder $builder) => $builder->where('phone', 'like', "%{$phone}%"));
        }
        return $query->paginate($request->per_page);
    }

}
