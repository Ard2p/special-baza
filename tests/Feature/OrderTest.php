<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $user = User::whereEmail('ruslan@trans-baza.ru')->first();
        $this->actingAs($user)
        ->post('rest/v1/payments/generate',[], [

                'HTTP_domain' => 'ru'

        ])
        ->assertStatus(200);
    }
}
