<?php

namespace Modules\Dispatcher\Document;

use App\User;
use Modules\Orders\Entities\Service\ServiceCenter;
use Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServiceCenterControllerTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    /**
     * @group service-center
     */
    public function testSuccessChangeContract()
    {
        $user = User::find(13);
        $this->actingAs($user, 'api');
        $this->withHeaders([
            'Branch' => '128',
            'Company' => 'company128'
        ]);

        $order_id = 120;

        $this->assertDatabaseHas('service_centers', [
            'id' => $order_id,
            'contract_id' => 1960
        ]);

        $payload = [
            'contract_id' =>  1958
        ];

        $response = $this->postJson('/rest/v1/service-center/' . $order_id . '/change-contract', $payload);

        $response
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('service_centers', [
            'id' => $order_id,
            'contract_id' => $payload['contract_id']
        ]);

        $response = $this->getJson('/rest/v1/service-center/' . $order_id);

        $response
            ->assertJsonStructure(['contract'])
            ->assertStatus(Response::HTTP_OK);
    }
}
