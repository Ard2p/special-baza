<?php

namespace App\Http\Controllers\Avito\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyOffice\Services\ContactsService;

/**
 * @OA\Schema(
 *      required={
 *          "token",
 *          "avito_order_id",
 *          "return_sum"
 *      },
 *      @OA\Xml(name="CancelOrderRequest")
 * )
 **/
class CancelOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @OA\Property(type="string", description="Токен для авторизации пользователя", property="token"),
     * @OA\Property(type="string", description="Номер заказа в Avito", property="avito_order_id"),
     * @OA\Property(type="integer", description="Сумма возврата", property="return_sum"),
     *
     * @return array
     */
    public function rules()
    {
        return [
            'token' => 'required|string',
            'avito_order_id' => 'required|string',
            'return_sum' => 'required|integer'
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
