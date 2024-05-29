<?php

namespace Tests\Modules\Dispatcher;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class LeadsControllerTest extends TestCase
{
    use WithFaker;
//        DatabaseTransactions;

    /**
     * @group leads
     */
    public function testUpdate()
    {
        $user = User::find(13);
        $this->actingAs($user, 'api');
        $this->withHeaders([
            'Branch' => '128',
            'Company' => 'company128'
        ]);

        $leadId = 1077;
        $contractId = 1987;
        $payload = [
            'contract_id' => $contractId,
            "id" => 594,
            "publish_type" => "my_proposals",
            "creator_id" => 13,
            "contacts" => [],
            "title" => "Заявка Колмагоров Космос Юрьевич д. 15 Батюнинский проезд Юго-Восточный административный округ Москва Печатники Москва",
            "documents_pack_id" => 1,
            "contractor_requisite_id" => "legal_7",
            "contact_person" => "Колмагоров Космос Юрьевич",
            "company_name" => "",
            "phone" => "+7 (955)-666-44-44",
            "city_id" => 1098,
            "source" => "call",
            "region_id" => 77,
            "address" => "д. 15 Батюнинский проезд Юго-Восточный административный округ Москва Печатники Москва",
            "comment" => "Колмагоров Космос Юрьевич",
            "object_name" => "д. 15 Батюнинский проезд Юго-Восточный административный округ Москва Печатники Москва",
            "pay_type" => "cashless",
            "start_date" => "2024-03-05 08:00:00",
            "status" => "open",
            "coordinates" => "55.66612440,37.69841720",
            "call_id" => "",
            "customer_type" => "Modules\Dispatcher\Entities\Customer",
            "duration" => 1,
            "customer_id" => 152,
            "client" => false,
            "order_type" => "shift",
            "vehicles_categories" => [
                [
                    "brand_id" => "",
                    "id" => 2,
                    "machinery_model_id" => null,
                    "order_duration" => 5,
                    "order_type" => "shift",
                    "month_duration" => 31,
                    "count" => 1,
                    "type" => "machine",
                    "is_month" => false,
                    "start_time" => "08:00",
                    "date_from" => "2024-02-15",
                    "params" => [
                    ],
                    "optional_attributes" => [
                    ],
                    "waypoint" => "",
                    "coordinates" => ""
                ]
            ],
            "customer_name" => "Колмагоров Космос Юрьевич",
            "deleted_at" => null,
            "created_at" => "2024-02-09 13:15:23",
            "updated_at" => "2024-02-19 13:09:55",
            "domain_id" => 1,
            "integration_unique" => null,
            "company_branch_id" => 128,
            "reject_type" => null,
            "rejected" => null,
            "internal_number" => 379,
            "contractor_requisite_type" => "App\User\EntityRequisite",
            "is_fast_order" => 0,
            "contract_sent" => null,
            "tmp_status" => "open",
            "tender" => true,
            "kp_date" => "2024-02-09 13:18:38",
            "accepted" => false,
            "first_date_rent" => null,
            "date" => "2024-02-15",
            "time" => "08:00",
            "can_edit" => true,
            "dispatcher_sum" => 0,
            "type" => "dispatcher",
            "status_lng" => "Новая",
            "status_date" => "2024-02-19 13:09:55"
        ];

        $response = $this->patchJson('rest/v1/admin/dispatcher/leads/' . $leadId, $payload);

        $this->assertDatabaseHas('dispatcher_leads', [
            'id' => $leadId,
            'customer_contract_id' => $contractId,
        ]);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>$json
                    ->where('customer_contract_id', $contractId)
                    ->etc()
            );
    }


    /**
     * @group mergepdf
     */
    public function testMergePDFSuccess()
    {
        $user = User::find(13);
        $this->actingAs($user, 'api');
        $this->withHeaders([
            'Branch' => '128',
            'Company' => 'company128'
        ]);

        $leadId = 1039;
        $payload = [
            'documents_ids' => [4908, 4910]
        ];

        $response = $this->postJson('rest/v1/dispatcher/leads/'.$leadId.'/merge-pdf', $payload);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>$json
                    ->where('order_id', $leadId)
                    ->where('order_type', "Modules\\Dispatcher\\Entities\\Lead")
                    ->etc()
            );
    }
}