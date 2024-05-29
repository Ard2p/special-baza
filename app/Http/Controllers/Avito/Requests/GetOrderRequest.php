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
class GetOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @OA\Parameter(name="token", in="query", description="Токен авторизации пользователя", required=true, @OA\Schema(type="string")),
     * @OA\Parameter( name="avito_order_id", in="query", description="Номер сделки в Avito", required=true, @OA\Schema(type="string")),
     *
     * @return array
     */
    public function rules()
    {
        return [
            'token' => 'required|string',
            'avito_order_id' => 'required|string',
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
