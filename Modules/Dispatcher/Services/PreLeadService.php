<?php


namespace Modules\Dispatcher\Services;


use App\Machinery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Dispatcher\Entities\PreLeadPosition;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;

/**
 * Class PreLeadService
 * @package Modules\Dispatcher\Services
 */
class PreLeadService
{

    /**
     * @var PreLead
     */
    public $preLead;

    /**
     * @var CompanyBranch
     */
    /**
     * @var CompanyBranch
     */
    /**
     * @var CompanyBranch
     */
    private $companyBranch, $dirtyData = [], $data = [];

    /**
     * PreLeadService constructor.
     * @param CompanyBranch $companyBranch
     */
    public function __construct(CompanyBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch;

    }

    /**
     * @param array $data
     * @return $this
     */
    function setData(array $data)
    {
        $this->dirtyData = $data;


        return $this;
    }

    /**
     * @param bool $update
     * @return $this
     */
    private function parseData($update = false)
    {
        if(!empty($this->dirtyData['date_from']) && !empty($this->dirtyData['time_from'])) {
            $data = Carbon::parse($this->dirtyData['date_from'])->format('Y-m-d');
            $date = Carbon::parse($data . ' ' . $this->dirtyData['time_from']);

        }

        $fields = [
            'name' => $this->dirtyData['name'] ?? '',
            'phone' => $this->dirtyData['phone'],
            'contact_person' => $this->dirtyData['contact_person'] ?? null,
            'email' => $this->dirtyData['email'] ?? null,
            'address' => $this->dirtyData['address'] ?? null,
            'coordinates' => $this->dirtyData['coordinates'] ?? null,
            'date_from' => $date ?? null,
            'order_duration' => $this->dirtyData['order_duration'] ?? 1,
            'order_type' =>  $this->dirtyData['order_type'] ?? null,
            'comment' => $this->dirtyData['comment'] ?? null,
            'object_name' => $this->dirtyData['object_name'] ?? null,
            'rejected' => '',
            'customer_id' => $this->dirtyData['customer_id'] ?? null,
            'positions' => $this->dirtyData['positions'],

        ];
        if (!$update) {
            $fields['company_branch_id'] = $this->companyBranch->id;
            $fields['creator_id'] = Auth::id();
        }

        $this->data = $fields;

        return $this;

    }

    function attachAttributes(PreLeadPosition $position, $attributes)
    {
        $position->attributes()->detach();
        foreach ($attributes as $id => $value)
        {
            if(!$value)
                continue;

            $position->attributes()->attach([$id => ['value' => $value]]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    function create()
    {

        $this->parseData();

        $this->preLead = PreLead::create($this->data);

        $this->setPositions();
        $source = null;

        if(!empty($this->dirtyData['call_id'])) {

            $this->attachCall($this->dirtyData['call_id']);
            $this->preLead->save();
            $this->preLead->source = Lead::SOURCE_CALL;
        }
        if(!empty($this->dirtyData['email_uuid'])) {

            $this->preLead->source = Lead::SOURCE_MAIL;
            $this->preLead->save();
            $this->attachMail($this->dirtyData['email_uuid']);
        }
        if($this->dirtyData['contacts'] ?? []){
            $this->preLead->id || $this->preLead->save();
            $this->preLead->addContacts($this->dirtyData['contacts']);
        }

        return $this;
    }

    /**
     * @param PreLead $preLead
     * @return $this
     */
    function update(PreLead $preLead)
    {
        $this->parseData(true);

        $this->preLead = $preLead;
        $this->preLead->update($this->data);

        $this->setPositions(true);
        if($this->dirtyData['contacts'] ?? []){
            $this->preLead->id || $this->preLead->save();
            $this->preLead->addContacts($this->dirtyData['contacts']);
        }

        return $this;
    }

    /**
     * @param bool $update
     * @return $this
     */
    private function setPositions($update = false)
    {
        if($update) {
            $this->preLead->positions()->delete();
        }
        foreach ($this->data['positions'] as $position) {
            $fields = [
                'pre_lead_id' => $this->preLead->id,
                'category_id' => $position['category_id'],
                'model_id' => $position['model_id'] ?? null,
                'brand_id' => $position['brand_id'] ?? null,
                'count' => $position['count'] ?? 1,
                'date_from' => $position['date_from'] ?? null,
                'time_from' => $position['time_from'] ?? null,
                'order_type' => $position['order_type'] ?? null,
                'order_duration' => $position['order_duration'] ?? null,
            ];
            if ($position['machinery_id'] ?? false) {
                $vehicle = Machinery::query()->forBranch()->findOrFail($position['machinery_id']);
                $fields['machinery_id'] = $vehicle->id;
                $fields['category_id'] = $vehicle->type;
                $fields['model_id'] = $vehicle->model_id;
                $fields['count'] = 1;
            }

            $pos = PreLeadPosition::create($fields);

            $this->attachAttributes($pos, $position['attributes'] ?? []);
        }

        return $this;
    }

    /**
     * @param $callId
     * @return $this
     */
    private function attachCall($callId)
    {
        /** @var TelephonyCallHistory $call */
        $call = $callId instanceof TelephonyCallHistory ?: TelephonyCallHistory::query()->forCompany($this->companyBranch->company->id)->find($callId);

        if($call) {
            $call->bind()->associate($this->preLead);

            $call->save();
        }

        return $this;
    }

    /**
     * @param $email_uuid
     * @return $this|bool
     */
    function attachMail($email_uuid)
    {
        if(!$email_uuid) {
            return false;
        }
        if($this->preLead->company_branch->mailConnector)
        {
            try {
                $this->preLead->company_branch->mailConnector->bindMail($this->preLead, $email_uuid);

            }catch (\Exception $exception) {

            }

        }

        return $this;
    }
}
