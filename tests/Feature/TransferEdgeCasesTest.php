<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransferEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response(['status' => 'success'], 200),
            'https://util.devi.tools/api/v1/notify' => Http::response(['message' => 'Success'], 200),
        ]);
    }

    /** @test */
    public function it_handles_decimal_values_correctly()
    {
        $payer = User::factory()->create(['type' => 'common', 'balance' => 100.50]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 50.25,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(201);

        $this->assertEquals('50.25', $payer->fresh()->balance);
        $this->assertEquals('50.25', $payee->fresh()->balance);
    }

    /** @test */
    public function it_handles_exact_balance_transfer()
    {
        $payer = User::factory()->create(['type' => 'common', 'balance' => 100]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(201);

        $this->assertEquals('0.00', $payer->fresh()->balance);
        $this->assertEquals('100.00', $payee->fresh()->balance);
    }

    /** @test */
    public function it_prevents_negative_transfer_amounts()
    {
        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => -100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(422);

        $this->assertEquals('1000.00', $payer->fresh()->balance);
        $this->assertEquals('0.00', $payee->fresh()->balance);
    }

    /** @test */
    public function it_maintains_data_integrity_with_multiple_transfers()
    {
        $user1 = User::factory()->create(['type' => 'common', 'balance' => 1000]);
        $user2 = User::factory()->create(['type' => 'common', 'balance' => 500]);
        $user3 = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $this->postJson('/api/transfer', [
            'value' => 200,
            'payer' => $user1->id,
            'payee' => $user2->id,
        ])->assertStatus(201);

        $this->postJson('/api/transfer', [
            'value' => 300,
            'payer' => $user2->id,
            'payee' => $user3->id,
        ])->assertStatus(201);

        $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $user1->id,
            'payee' => $user3->id,
        ])->assertStatus(201);

        $this->assertEquals('700.00', $user1->fresh()->balance);
        $this->assertEquals('400.00', $user2->fresh()->balance);
        $this->assertEquals('400.00', $user3->fresh()->balance);
        $this->assertEquals(3, Transaction::count());
    }

    /** @test */
    public function it_handles_large_amounts_correctly()
    {
        $payer = User::factory()->create(['type' => 'common', 'balance' => 999999.99]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 500000.50,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(201);

        $this->assertEquals('499999.49', $payer->fresh()->balance);
        $this->assertEquals('500000.50', $payee->fresh()->balance);
    }

    /** @test */
    public function user_model_identifies_merchant_correctly()
    {
        $merchant = User::factory()->create(['type' => 'merchant']);
        $common = User::factory()->create(['type' => 'common']);

        $this->assertTrue($merchant->isMerchant());
        $this->assertFalse($common->isMerchant());
    }

    /** @test */
    public function user_model_checks_if_user_can_send_transfer()
    {
        $merchant = User::factory()->create(['type' => 'merchant']);
        $common = User::factory()->create(['type' => 'common']);

        $this->assertFalse($merchant->canSendTransfer());
        $this->assertTrue($common->canSendTransfer());
    }

    /** @test */
    public function user_model_checks_sufficient_balance_correctly()
    {
        $user = User::factory()->create(['balance' => 100]);

        $this->assertTrue($user->hasSufficientBalance(50));
        $this->assertTrue($user->hasSufficientBalance(100));
        $this->assertFalse($user->hasSufficientBalance(150));
    }
}
