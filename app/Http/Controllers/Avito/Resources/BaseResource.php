<?php

namespace App\Http\Controllers\Avito\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *  @OA\Xml(name="BaseResource")
 * )
 **/
class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @OA\Property(type="integer", description="Статус запроса (1 – Успешно, 2 – Ошибка)", property="status"),
     * @OA\Property(type="string", description="Сообщение об ошибке (если статус запроса 2)", property="error_message"),
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "status" => $this->status,
            "error_message" => $this->error_message
        ];
    }
}
