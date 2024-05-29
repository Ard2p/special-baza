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

class AdminAvitoControllerTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * @group avito-orders
     */
    public function testSuccessGetOrders()
    {
        $user = User::find(13);
        $this->actingAs($user, 'api');
        $this->withHeaders([
            'Branch' => '128',
            'Company' => 'company128'
        ]);

        $payload = [];

        $response = $this->getJson('rest/v1/admin/avito/orders', $payload);

        dd($response->getContent());

        $response
            ->assertStatus(Response::HTTP_OK);
    }
}
