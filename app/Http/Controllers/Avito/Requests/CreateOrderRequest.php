<?php

namespace App\Http\Controllers\Avito\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyOffice\Services\ContactsService;
/**
 * @OA\Schema(
 *      required={
 *          "token",
 *          "avito_order_id",
 *      },
 *      @OA\Xml(name="CreateOrderRequest")
 * )
 **/
class CreateOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @OA\Property(type="string", description="Токен", property="token"),
     * @OA\Property(type="string", description="Источник заказа", property="from"),
     * @OA\Property(type="string", description="Номер заказа Avito", property="avito_order_id"),
     * @OA\Property(type="string", description="Номер заказа Avito", property="comment"),
     * @OA\Property(type="integer", description="Avito ID", property="avito_ad_id"),
     * @OA\Property(type="integer", description="Стоимость объявления", property="avito_add_price"),
     * @OA\Property(type="string", description="Наименование объявления", property="avito_ad_title"),
     * @OA\Property(type="object", description="Координаты", property="geo",
     *          @OA\Property(type="string", description="Адрес аренды", property="rent_address"),
     * ),
     * @OA\Property(type="object", description="Даты", property="dates",
     *          @OA\Property(type="string", format="date-time", description="Дата начала аренды", property="start_date_from"),
     *          @OA\Property(type="string", format="date-time", description="Дата окончания аренды", property="start_date_to"),
     *          @OA\Property(type="integer", description="Продолжительность аренды", property="rental_duration"),
     *
     * ),
     * @OA\Property(type="object", description="Клиент (Заказчик)", property="customer",
     *
     *          @OA\Property(type="string", description="ФИО", property="name"),
     *          @OA\Property(type="string", description="Телефон", property="phone"),
     *          @OA\Property(type="string", description="Email", property="email"),
     *          @OA\Property(type="string", description="ИНН", property="inn"),
     *          @OA\Property(type="integer", description="Тип клиента", property="type")
     * )
     *
     * @return array
     */
    public function rules()
    {
        return  [
            'token' => 'string|required',
            'from' => 'string|nullable',
            'avito_order_id' => 'string|required',
            'avito_ad_id' => 'integer|required',
            'avito_add_price' => 'integer|nullable',
            'comment' => 'string|nullable',
            'geo' => 'array|required',
            'geo.coordinate_x' => 'string|nullable',
            'geo.coordinate_y' => 'string|nullable',
            'geo.rent_address' => 'string|required',

            'dates' => 'array|required',
            'dates.start_date_from' => 'string|required',
            'dates.start_date_to' => 'string|required',
            'dates.rental_duration' => 'integer|required',
            'dates.name' => 'string',

            'customer' => 'array|required',
            'customer.name' => 'string|nullable',
            'customer.phone' => 'string|required',
            'customer.email' => 'string|nullable',
            'customer.inn' => 'string|nullable',
            'customer.type' => 'integer|nullable',
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
