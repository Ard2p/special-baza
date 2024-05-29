<?php

namespace Modules\ContractorOffice\Transformers;

use App\Machinery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherInvoiceDepositTransfer;
use Modules\Orders\Entities\Payments\InvoicePay;
use function Clue\StreamFilter\fun;

class OrdersCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        /* $showLead = false;
         if ($this->lead && $this->lead->user_id === $this->user_id) {
             $showLead = true;
             $this->lead->categories = Type::setLocaleNames($this->lead->categories);
             $this->lead->load('customer');
         }*/
     //  $paidQuery = InvoicePay::query()->whereHasMorph('invoice', [DispatcherInvoice::class], function (Builder $q) {
     //      $q->whereHasMorph('owner', [\Modules\Orders\Entities\Order::class], function (Builder $q) {
     //          $q->where('orders.id', $this->id);
     //      });
     //  });

        /** @var Collection $invoices */
        $invoices = $this->invoices;
        $pays = $invoices->flatMap(fn($invoice) => $invoice->pays);

        $paidSum = $pays->sum(fn($pay) => $pay->operation === 'in' ? $pay->sum : (-$pay->sum));
        $paidSum += $this->depositTransfer->sum('sum');

        $services = 0;
        $this->components->each(function ($item) use (&$services) {
            foreach ($item->services as $service) {
                if ($service->customService->is_pledge) {
                    $services += $service->price;
                }
            }
        });
        if ($request->filled('has_tasks_customer_id')) {
            $latestTask = $this->tasks()->whereHasMorph('customers', Customer::class, function ($q) use ($request) {
                $q->has('tasks')->where('id', $request->has_tasks_customer_id);
            })->latest()->first();
        } else {
            $latestTask = $this->tasks->last();
        }
        $vehicles = $this->components->filter(fn($c) => $c->status !== \Modules\Orders\Entities\Order::STATUS_REJECT)->map(function($c) {
          $result = $c->worker;
             $result->pivot = (object) $c->only([
                 'id', 'complete', 'application_id', 'comment', 'cost_per_unit', 'return_delivery', 'actual',
                 'amount', 'category_id', 'date_from', 'date_to', 'delivery_cost', 'order_type', 'order_duration',
                 'insurance_cost', 'services',
                 'reject_type', 'worker_type', 'value_added', 'services_sum_value_added', 'services_sum', 'parts'
             ]);
            $result->shift_duration = $result->change_hour * 1;
            $result->has_certificate = $c->ins_certificate()->exists();
          return $result;
        } )->unique('id');
        return [
            'id' => $this->id,
            'name' => $this->name,
            'external_id' => $this->external_id,
            'internal_number' => $this->internal_number,
            'customer' => $this->customer,
            'channel' => $this->channel,
            'manager' => $this->manager,
            'machinery_sets' => $this->machinerySets,
            'has_driver' => $this->has_driver,
            'status_fake' => $this->status_fake,
            'status_date' => $this->status_date,
            'status_lang' => $this->status_lang,
            // 'contacts' => $this->contacts,
            'dispatcher_contractors' => $this->dispatcher_contractors,
            'dispatcher_categories' => $this->getDispatcherCategories(),
            'vehicles' => $vehicles->map(function ($v) {
                $v->pivot->date_from = Carbon::parse($v->pivot->date_from)->toDateTimeString();
                $v->pivot->date_to = Carbon::parse($v->pivot->date_to)->toDateTimeString();
                $parts = $v->parts?->map(function ($q) {
                    $d['name'] = $q->part->name;
                    $d['count'] = $q->pivot->count;
                    return $d;
                });
                if($v->pivot->worker_type === Machinery::class){
                    $parts = $v->pivot->parts?->map(function ($q) {
                        $d['name'] = $q->part->name;
                        $d['count'] = $q->amount;
                        return $d;
                    });
                }
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'order_duration' => $v->pivot->order_duration,
                    'value_added' => $v->pivot->value_added,
                    'actual' => $v->pivot->actual,
                    'has_certificate' => $v->has_certificate,
                    'is_machinery' => $v->pivot->worker_type === Machinery::class,
                    'subOwner' => $v->subOwner?->company_name,
                    'pivot' => $v->pivot,
                    'parts' => $parts,
                    'shift_duration' => $v->change_hour * 1,
                    'amount' => $v->pivot->amount,
                    'services_sum' => $v->pivot->services_sum,
                    'services_sum_value_added' => $v->pivot->services_sum_value_added,
                    'reject_type' => $v->pivot->reject_type,
                    'order_timestamps' => $v->order_timestamps->where('order_id', $this->id)->values()->all(),
                ];
            })->values()->all(),
            'has_sub_owner' => !!$vehicles->filter(fn ($v) => $v->subOwner)->isNotEmpty(),
           // 'vehicles_count' => $this->getVehiclesCount(),
            'date_from' => (string) $this->date_from,
            'date_to' => (string) $this->date_to,
            'created_at' => (string) $this->created_at,
            'sum_format' => $this->sum_format,
           // 'types_list_text' => $this->types_list_text,
         //   'status_lang' => $this->status_lang,
            'status' => $this->status,
            'type' => $this->type,
            'watch_as' => 'contractor',
            'contractor_pays_sum' => $this->components->sum('contractor_pays_sum'),
            'invoice' => [
                'value_added' => (float)$this->components->filter(fn($c) => $c->status !== \Modules\Orders\Entities\Order::STATUS_REJECT)->sum(function ($item) {
                    return $item->services->sum(fn($service)=> $service->value_added * $service->count) + $item->value_added * $item->order_duration;
                    }),
                'sum' => (float) $this->invoices->sum('sum'),
                'paid' => (float) $paidSum,
            ],
            'pledge' => [
                'total' => $services,
                'in' => (float) $pays
                    ->where('method', 'pledge')
                    ->where('operation', 'in')
                    ->sum('sum'),
                'out' => (float) $pays
                    ->where('method', 'pledge')
                    ->where('operation', 'out')
                    ->sum('sum'),
            ],
            'company_branch_id' => $this->company_branch_id,
            'categories' => $this->getOrderCategories(),

            /*  $this->mergeWhen($showLead, [
                  'lead' => $this->lead,
              ]),*/
            //  'payment'                => $this->payment,
            'creator' => $this->creator,
            'comment' => $this->comment,
            'machinery_base' => $this->machineryBase,
            'source' => $this->from,
            'contract' => $this->contract,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->manager->phone,
            'contact_email' => $this->manager->email,
            'address' => $this->address,
            'amount' => $this->amount,
            'amount_actual' => $this->components->sum(fn($component) => $component->actual?->total_sum ?: 0),
            'currency' => $this->currency,
            'tasks_count' => $this->tasks_count,
            'latest_task' => ($latestTask) ? $latestTask->date_from : null,
            'documents' => [],
            'object_name' =>  $this->lead?->object_name,

            //    'contractor_cost' => $this->getContractorCost(),
            'coordinates' => $this->coordinates,
            'rent_amount' => $this->components->sum('total_sum'),
            'services_amount' => $this->components->sum('services_sum'),
            'parts_amount' => $this->components->sum('parts_sum'),
            'delivery_amount' => $this->components->sum('delivery_cost'),
            'services_sum_value_added' => $this->components->sum('services_sum_value_added'),
            'return_delivery_amount' => $this->components->sum('return_delivery'),
        ];
    }
}




