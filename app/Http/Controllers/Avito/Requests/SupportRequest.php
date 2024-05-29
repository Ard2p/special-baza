<?php

namespace App\Http\Controllers\Avito\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyOffice\Services\ContactsService;

/**
 * @OA\Schema(
 *      required={
 *          "token",
 *          "avito_order_id"
 *      },
 *      @OA\Xml(name="SupportRequest")
 * )
 **/
class SupportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @OA\Property(type="string", description="Токен для авторизации пользователя", property="token"),
     * @OA\Property(type="integer", description="Номер заказа в Avito", property="avito_order_id")
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
