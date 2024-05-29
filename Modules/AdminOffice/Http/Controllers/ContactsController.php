<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Helpers\RequestHelper;
use App\Machinery;
use App\Option;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Entities\RpContact;
use Modules\AdminOffice\Entities\SiteFeedback;

class ContactsController extends Controller
{

    public function __construct(Request $request)
    {
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->input('phone'))
            ]);
        }
        $data = $request->all();
        $data = array_map(function ($val) {
            return $val === 'null' || $val === 'undefined' ? '' : $val;
        }, $data);
        $request->merge($data);
    }

    function get(Request $request, $id = null)
    {
        $contacts = RpContact::query()->forDomain();

        $filter = new Filter($contacts);
        $filter->getLike([
            'name' => 'name',
        ]);


        return $contacts->paginate($request->per_page ?: 10);
    }

    function getCompanyRequisites(Request $request)
    {
        $company = Option::get('company_requisite_' . $request->country);
        return \response()->json($company ? json_decode($company) : []);
    }

    function setCompanyRequisites(Request $request)
    {
        $errors = \Validator::make($request->all(), [
            'country' => 'required|in:australia,russia',
            'requisites' => 'required|array',
            'requisites.*.name' => 'required|string',
            'requisites.*.value' => 'required|string',

        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 419);
        }

       Option::set('company_requisite_' . $request->country, json_encode($request->input('requisites')));

        return $this->getCompanyRequisites($request);

    }

    function create(Request $request)
    {
        $errors = \Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'email|nullable',
            'phone' => 'numeric|digits:' .  RequestHelper::requestDomain()->options['phone_digits'] . '|nullable',
            'region' => 'string|nullable',
            'position' => 'string|nullable',
            'is_supervisor' => 'nullable|in:true,false',
            'is_rp' => 'nullable|in:true,false',
            'user_id' => 'nullable|exists:users,id',
            'country_id' => 'required|exists:countries,id',

        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 419);
        }

        RpContact::create(
            [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'region' => $request->input('region'),
                'position' => $request->input('position') ,
                'is_supervisor' => filter_var($request->input('is_supervisor'), FILTER_VALIDATE_BOOLEAN),
                'is_rp' => filter_var($request->input('is_rp'), FILTER_VALIDATE_BOOLEAN),
                'user_id' => $request->input('user_id'),
                'country_id' => $request->input('country_id'),
            ]);

        return response()->json();
    }

    function update(Request $request, $id)
    {
        $contact = RpContact::findOrFail($id);
        $errors = \Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'email|nullable',
            'phone' => 'numeric|digits:' .  RequestHelper::requestDomain()->options['phone_digits'] .'|nullable',
            'region' => 'string|nullable',
            'position' => 'string|nullable',
            'is_supervisor' => 'nullable|in:true,false,0,1',
            'is_rp' => 'nullable|in:true,false,0,1',
            'user_id' => 'nullable|exists:users,id',
            'country_id' => 'required|exists:countries,id',
        ])->errors()->getMessages();
        if ($errors) {
            return response()->json($errors, 419);
        }
        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'region' => $request->input('region'),
            'position' => $request->input('position') ,
            'is_supervisor' => filter_var($request->input('is_supervisor'), FILTER_VALIDATE_BOOLEAN) ,
            'is_rp' => filter_var($request->input('is_rp'), FILTER_VALIDATE_BOOLEAN),
            'user_id' => $request->input('user_id'),
            'country_id' => $request->input('country_id'),
        ];
        $data = array_map(function ($val){
           return $val === 'null' ? '' : $val;
        }, $data);
        $contact->update($data);

        return response()->json();
    }

    function delete($id)
    {
        RpContact::findOrFail($id)->delete();
    }
}
