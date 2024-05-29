<?php

namespace App\Http\Controllers\Avito\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *  @OA\Xml(name="OrderResource")
 * )
 **/
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @OA\Property(type="string", description="Номер сделки в Avito", property="avito_order_id"),
     * @OA\Property(type="array", description="", property="worker",
     *      @OA\Items(
     *          @OA\Property(type="integer", description="Внутренний ID исполнителя", property="id"),
     *          @OA\Property(type="string", description="Наименование исполнителя", property="name"),
     *          @OA\Property(type="string", description="Контактное лицо исполнителя", property="contact"),
     *          @OA\Property(type="string", description="Телефон контактного лица исполнителя", property="phone"),
     *          @OA\Property(type="string", description="Адрес исполнителя", property="address"),
     *          @OA\Property(type="string", description="ИНН исполнителя", property="inn"),
     *          @OA\Property(type="integer", description="Количество выполненных заказов исполнителя", property="orders_count"),
     *     )
     * ),
     * @OA\Property(type="array", description="", property="machinery",
     *      @OA\Items(
     *          @OA\Property(type="integer", description="Внутренний ID техники", property="machinery_id"),
     *          @OA\Property(type="string", format="date-time", description="Дата начала аренды", property="date_from"),
     *          @OA\Property(type="string", format="date-time", description="Дата окончания аренды", property="date_to"),
     *          @OA\Property(type="string", description="Наименование техники", property="name"),
     *          @OA\Property(type="string", description="Серийный номер техники", property="serial_number"),
     *          @OA\Property(type="integer", description="Колличество смен", property="shift_count"),
     *          @OA\Property(type="integer", description="Цена за единицу техники в копейках", property="cost_per_unit"),
     *          @OA\Property(type="integer", description="Стоимость аренды техники в копейках", property="rent_cost"),
     *          @OA\Property(type="integer", description="Стоимость доставки техники в копейках", property="delivery_cost"),
     *          @OA\Property(type="array", description="Счета", property="invoices",
     *              @OA\Items(
     *                  @OA\Property(type="integer", description="Внутренний ID счета", property="invoice_id"),
     *                  @OA\Property(type="string", description="Номер счета", property="invoice_number"),
     *                  @OA\Property(type="integer", description="Статус счета (1 – Не оплачен, 2 – Оплачен, 3 - Отменен)", property="invoice_status"),
     *                  @OA\Property(type="string", format="date-time", description="Дата счета", property="invoice_date"),
     *                  @OA\Property(type="integer", description="Тип оплаты (1 – Банковская карта, 2 – Расчетный счет)", property="payment_type"),
     *                  @OA\Property(type="string", description="Ссылка на оплату по БК", property="online_payment_url"),
     *                  @OA\Property(type="string", description="Ссылка на документ счета (PDF)", property="invoice_url"),
     *                  @OA\Property(type="integer", description="Сумма счета в копейках", property="invoice_sum")
     *              )
     *          ),
     *          @OA\Property(type="array", description="Документы", property="documents",
     *              @OA\Items(
     *                  @OA\Property(type="integer", description="Внутренний ID документа", property="doc_id"),
     *                  @OA\Property(type="integer", description="Тип документа (1 – Договор, 2 – Счет, 3 – Акт сдачи, 4 – Акт приема, 5 – Спецификация на аренду)", property="doc_type"),
     *                  @OA\Property(type="string", description="Ссылка на документ", property="doc_url"),
     *                  @OA\Property(type="string", format="date-time", description="Дата документа", property="doc_date")
     *              )
     *          ),
     *          @OA\Property(type="array", description="Участники сделки", property="participants")
     *      )
     * ),
     * @OA\Property(type="integer", description="Статус запроса (1 – Успешно, 2 – Ошибка)", property="status"),
     * @OA\Property(type="string", description="Сообщение об ошибке (если статус запроса 2)", property="error_message"),
     *
     *
     *
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->cancel_reason) {
            $order = $this->order;

            if (!$this->order) {
                return [
                    "status" => 2,
                    "error_message" => "Заказ не найден"
                ];
            }
            $companyBranch = $order->company_branch;
            $companyBranchRequisites = $companyBranch->requisite->first();
            $component = $order->workers->first();
            $machinery = $component?->worker;
            $participants = null;
            if ($component->order->contractor) {
                $participants['contractor'] = [
                    "id" => $companyBranch->id,
                    'name' => $component->order->contractor->name,
                    "contact" => $order->creator->contact_person,
                    "phone" => "+" . $order->creator->phone,
                    "address" => $companyBranchRequisites->register_address,
                    "inn" => $companyBranchRequisites->inn,
                    "orders_count" => 0,
                ];
            }
            $driver = null;
            if ($component->order->driver) {
                $driver = $component->order->driver;
                $participants['driver'] = [
                    'name' => $driver->full_name,
                    'phone' => $driver->phone,
                    'passport' => $driver->passport,
                    'machinery_number' => $driver->machinery_number,
                    'brand' => $driver->brand,
                ];
            }

            $request->merge([
                'inv_url' => $order->documents->where('ext_type', 'pdf')->where('type', 'avito_invoice')->first()?->url
            ]);

            $ssl = config('app.ssl');
            $frontUrl = config('app.front_url');
            $companyBranchId = $order->company_branch->id;
            $companyAlias = $order->company_branch->company->alias;

            $url = "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/$order->id";

            $data = [
                "avito_order_id" => $this->avito_order_id,
                "order_status" => $this->status,
                'url' => $url,
                "worker" => [
                    "id" => $companyBranch->id,
                    "name" => $companyBranchRequisites->short_name,
                    "contact" => $order->creator->contact_person,
                    "phone" => "+".$order->creator->phone,
                    "address" => $companyBranchRequisites->register_address,
                    "inn" => $companyBranchRequisites->inn,
                    "orders_count" => 0,
                ],

                "status" => 1,
                "error_message" => ""
            ];
            if ($machinery) {
                $data = [
                    ...$data,
                    "machinery" => [
                        [
                            "machinery_id" => $machinery->id,
                            "date_from" => $component->date_from,
                            "date_to" => $component->date_to,
                            "name" => $machinery->name,
                            "shift_count" => $component->order_duration,
                            'machinery_number' => $driver?->machinery_number,
                            'brand' => $driver?->brand,
                            "serial_number" => $machinery->serial_number,
                            "cost_per_unit" => $component->cost_per_unit,
                            "rent_cost" => $component->cost_per_unit * $component->order_duration,
                            "delivery_cost" => $component->delivery_cost + $component->return_delivery,
                            "invoices" => OrderInvoiceResource::collection($order->invoices->where('type','!=','avito_dotation')->where('is_paid',0)),
                            "documents" => OrderDocumentResource::collection($order->documents()->where('name','not like','%-AV%')->whereIn('type', ['upd','avito_invoice'])->where('ext_type', 'pdf')->get()),
                        ]
                    ],
                ];
            }
            if ($participants) {
                $data = [
                    ...$data,
                    "participants" => $participants,
                ];
            }
        } else {
            $data = [
                "avito_order_id" => $this->avito_order_id,
                'cancel_reason' => $this->cancel_reason,
                'cancel_reason_message' => $this->cancel_reason_message,
                "status" => 1,
                "error_message" => ""
            ];
        }

        return $data;
    }
}
