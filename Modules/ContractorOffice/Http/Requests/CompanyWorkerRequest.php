<?php

namespace Modules\ContractorOffice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\ContractorOffice\Entities\CompanyWorker;

class CompanyWorkerRequest extends FormRequest
{


    protected function prepareForValidation()
    {
        $contact = $this->input('contact');
        unset($contact['type']);

        $this->merge($contact);
    }


    function getDriverRules()
    {
        $rule = [
            'driver_document.driving_licence_number' => 'nullable|string|max:255',
            'driver_document.driving_licence_expired_date' => 'nullable|string|max:255',
            'driver_document.driving_licence_place_of_issue' => 'nullable|string|max:255',
            'driver_document.driving_licence_scans' => 'nullable|array',

            'driver_document.machinery_licence_number' => 'nullable|string|max:255',
            'driver_document.machinery_licence_scans' => 'nullable|array',
            'driver_document.machinery_licence_place_of_issue' => 'nullable|string|max:255',
            'driver_document.machinery_licence_date_of_issue' => 'nullable|string|max:255',
            'driver_document.driving_categories' => 'nullable|array',

            'driver_document.driving_categories.*.driving_category_id' => 'required|exists:driving_categories,id|distinct',
            'driver_document.driving_categories.*.date_of_issue' => 'required|date',
            'driver_document.driving_categories.*.expired_date' => 'required|date',
            'driver_document.driving_categories.*.experience_start' => 'required|date',

            'driver_document.machinery_categories.*.driving_category_id' => 'required|exists:driving_categories,id',
            'driver_document.machinery_categories.*.date_of_issue' => 'required|date',
            'driver_document.machinery_categories.*.expired_date' => 'required|date',
            'driver_document.machinery_categories.*.experience_start' => 'required|date',

        ];

        return $rule;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'type' => "required|in:" . implode(',', [
                    CompanyWorker::TYPE_DRIVER,
                    CompanyWorker::TYPE_MECHANIC
                ]),
            'photos' => 'nullable|array',
            'photos.*' => 'required|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'passport_scans' => 'nullable|array',
            'passport_place_of_issue' => 'nullable|string|max:255',
            'passport_date_of_issue' => 'nullable|string|max:255',
        ];

        if ($this->type === CompanyWorker::TYPE_DRIVER) {
            $rules += $this->getDriverRules();
        }
        return $rules + ContactsService::getValidationRules(true);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
