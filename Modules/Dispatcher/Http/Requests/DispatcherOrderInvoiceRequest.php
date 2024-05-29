<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Service\RequestBranch;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Orders\Entities\Order;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;

class DispatcherOrderInvoiceRequest extends FormRequest
{

    public  $order;


    public function messages()
    {
        return [
            'sum.max' => trans('validation.invoice_sum_max')
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $id = app()->make(RequestBranch::class)->companyBranch->id;

        if($this->input('owner_type')  === 'order') {

            $this->order = Order::forBranch($id)->findOrFail($this->input('owner_id'));

        }
        if($this->input('owner_type')  === 'partSale') {

            $this->order = PartsSale::forBranch($id)->withCost()->findOrFail($this->input('owner_id'));

        }

        return [
            'owner_id' => 'required|integer|min:1',
            'dispatcher_requisite' => 'required',
            'owner_type' => 'required|in:order,partSale',
            'dispatcher_requisite.id' => 'required',
            'dispatcher_requisite.type' => 'required|in:legal,individual',
            'customer_requisite.id' => 'required',
            'customer_requisite.type' => 'required|in:legal,individual',
            'sum' => 'required|numeric|min:1|max:' . (( ($this->order instanceof Order ? $this->order->amount : $this->order->cost) - $this->order->invoices_paid) / 100)
        ];
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
