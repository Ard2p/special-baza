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

class ContractTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    // public function setUp()
    // {
    //     $user = User::find(13);
    //     $this->actingAs($user, 'web');
    //     $this->withHeaders([
    //         'Branch' => '128',
    //         'Company' => 'company128'
    //     ]);
    // }

    /**
     * @group documents
     */
    public function testCreateContractSuccess()
    {
        $user = User::find(13);
        $this->actingAs($user, 'api');
        $this->withHeaders([
            'Branch' => '128',
            'Company' => 'company128'
        ]);

        $customer_id = 151;
        $customer_type = 'Modules\\Dispatcher\\Entities\\Customer';
        $payload = [
            'date' => now()->toDateTimeString(),
            'start_date' => now()->toDateTimeString(),
            'end_date' => now()->toDateTimeString(),
            'is_active' => true,
            'contragent_type' => 'customer',
            'requisites' => 'legal_94'
        ];

        $response = $this->postJson('rest/v1/dispatcher/customers/generate-contract/' . $customer_id, $payload);

        $this->assertDatabaseHas('dispatcher_customer_contracts', [
            'customer_id' => $customer_id,
            'customer_type' => $customer_type,
            'created_at' => $payload['date'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'type' => 'rent',
            'is_active' => $payload['is_active'],
            'last_application_id' => 0,
        ]);

        $response
            ->assertJson(
                fn (AssertableJson $json) =>              
                    $json->where('customer_id', $customer_id)
                    ->where('customer_type',  $customer_type)
                    ->where('created_at', $payload['date'])
                    ->where('start_date', $payload['start_date'])
                    ->where('end_date', $payload['end_date'])
                    ->where('is_active', $payload['is_active'])
                    ->where('last_application_id', 0)
                    ->where('type', 'rent')
                    ->etc()
            )  
            ->assertJsonStructure([
                'number',
                'customer_id',
                'customer_type',
                'current_number',
                'last_application_id',
                'created_at',
                'type',
                'name',
                'start_date',
                'end_date',
                'subject_type',
                'is_active'
            ])
            ->assertStatus(Response::HTTP_CREATED);
    }

    /**
     * @group documents
     */
    public function testDownloadSuccess()
    {
        $user = User::find(13);
        $this->actingAs($user, 'api');
        $this->withHeaders([
            'Branch' => '128',
            'Company' => 'company128'
        ]);

        $contract_id = 1983;
        $payload = [
            'date' => now()->format('d-m-Y'),
            'time' => null,
            'name' => 'Документ Тест',
            'documents_pack_id' => 1,
            'with_stamp' => null,
            'signatory_id' => null
        ];

        $response = $this->postJson('rest/v1/dispatcher/customers/download-contract/' . $contract_id, $payload);

        dd($response->getContent());
    }
}
