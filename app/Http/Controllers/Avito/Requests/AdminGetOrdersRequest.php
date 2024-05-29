<?php

namespace App\Http\Controllers\Avito\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *      required={
 *          "token",
 *          "avito_order_id"
 *      },
 *      @OA\Xml(name="GetOrderRequest")
 * )
 **/
class AdminGetOrdersRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @OA\Parameter(name="token", in="query", description="Токен авторизации пользователя", required=true, @OA\Schema(type="string")),
     *
     * @return array
     */
    public function rules()
    {
        // Todo: переделать на енум
        return [
            'pay_method' => 'string',
            'branch' => 'string',
            'customer' => 'string',
            'created_at' => 'string',
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
