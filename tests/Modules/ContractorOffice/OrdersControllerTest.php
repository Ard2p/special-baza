<?php

namespace Modules\Dispatcher\Document;

use App\User;
use Tests\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OrdersControllerTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * @group orders
     */
    public function testSuccessChangeContract()
    {
        $user = User::find(13);
        $this->actingAs($user, 'api');
        $this->withHeaders([
            'Branch' => '128',
            'Company' => 'company128'
        ]);

        $order_id = 1080;

        $this->assertDatabaseHas('orders', [
            'id' => $order_id,
            'contract_id' => 1984
        ]);

        $payload = [
            'contract_id' =>  1983
        ];

        $response = $this->postJson('rest/v1/user/contractor/orders/' . $order_id . '/change-contract', $payload);
//
        $response
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('orders', [
            'id' => $order_id,
            'contract_id' => $payload['contract_id']
        ]);

        $response = $this->getJson('rest/v1/user/contractor/orders/' . $order_id);

        $response
            ->assertJsonStructure(['contract'])
            ->assertStatus(Response::HTTP_OK);
    }
}
