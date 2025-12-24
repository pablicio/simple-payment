<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function mockSuccessfulAuthorization(): void
    {
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response(['status' => 'success'], 200),
            'https://util.devi.tools/api/v1/notify' => Http::response(['message' => 'Success'], 200),
        ]);
    }

    /** @test */
    public function it_can_transfer_successfully_between_common_users()
    {
        $this->mockSuccessfulAuthorization();

        $payer = User::factory()->create([
            'type' => 'common',
            'balance' => 1000,
        ]);

        $payee = User::factory()->create([
            'type' => 'common',
            'balance' => 500,
        ]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Transfer completed successfully',
            ]);

        $this->assertEquals('900.00', $payer->fresh()->balance);
        $this->assertEquals('600.00', $payee->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'value' => 100,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_can_transfer_from_common_user_to_merchant()
    {
        $this->mockSuccessfulAuthorization();

        $payer = User::factory()->create([
            'type' => 'common',
            'balance' => 1000,
        ]);

        $merchant = User::factory()->create([
            'type' => 'merchant',
            'balance' => 0,
        ]);

        $response = $this->postJson('/api/transfer', [
            'value' => 250,
            'payer' => $payer->id,
            'payee' => $merchant->id,
        ]);

        $response->assertStatus(201);

        $this->assertEquals('750.00', $payer->fresh()->balance);
        $this->assertEquals('250.00', $merchant->fresh()->balance);
    }

    /** @test */
    public function it_prevents_merchant_from_sending_transfer()
    {
        $this->mockSuccessfulAuthorization();

        $merchant = User::factory()->create([
            'type' => 'merchant',
            'balance' => 1000,
        ]);

        $payee = User::factory()->create([
            'type' => 'common',
            'balance' => 0,
        ]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $merchant->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Merchants cannot send transfers',
            ]);

        $this->assertEquals('1000.00', $merchant->fresh()->balance);
        $this->assertEquals('0.00', $payee->fresh()->balance);
    }

    /** @test */
    public function it_prevents_transfer_with_insufficient_balance()
    {
        $this->mockSuccessfulAuthorization();

        $payer = User::factory()->create([
            'type' => 'common',
            'balance' => 50,
        ]);

        $payee = User::factory()->create([
            'type' => 'common',
            'balance' => 0,
        ]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Insufficient balance',
            ]);

        $this->assertEquals('50.00', $payer->fresh()->balance);
        $this->assertEquals('0.00', $payee->fresh()->balance);
    }

    /** @test */
    public function it_prevents_user_from_transferring_to_themselves()
    {
        $this->mockSuccessfulAuthorization();

        $user = User::factory()->create([
            'type' => 'common',
            'balance' => 1000,
        ]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $user->id,
            'payee' => $user->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payer']);

        $this->assertEquals('1000.00', $user->fresh()->balance);
    }

    /** @test */
    public function it_requires_all_mandatory_fields()
    {
        $response = $this->postJson('/api/transfer', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value', 'payer', 'payee']);
    }

    /** @test */
    public function it_validates_value_must_be_positive()
    {
        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 0,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
    }

    /** @test */
    public function it_validates_payer_must_exist()
    {
        $payee = User::factory()->create(['type' => 'common']);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => 9999,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payer']);
    }

    /** @test */
    public function it_validates_payee_must_exist()
    {
        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => 9999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payee']);
    }

    /** @test */
    public function it_blocks_transfer_if_external_authorizer_fails()
    {
        // Desabilitar mock de ambiente para usar Http::fake
        config(['transfer.authorizer_mock' => false]);
        
        // Sobrescrever o mock do setUp com um que falha
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response(['status' => 'fail'], 200),
            'https://util.devi.tools/api/v1/notify' => Http::response(['message' => 'Success'], 200),
        ]);

        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Transfer not authorized',
            ]);

        $this->assertEquals('1000.00', $payer->fresh()->balance);
        $this->assertEquals('0.00', $payee->fresh()->balance);
    }

    /** @test */
    public function it_is_atomic_and_rolls_back_on_error()
    {
        // Desabilitar mock de ambiente para usar Http::fake
        config(['transfer.authorizer_mock' => false]);
        
        // Sobrescrever o mock do setUp com um que falha na autorização
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response(['status' => 'fail'], 200),
            'https://util.devi.tools/api/v1/notify' => Http::response(['message' => 'Success'], 200),
        ]);

        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $this->assertEquals(0, Transaction::count());
        $this->assertEquals('1000.00', $payer->fresh()->balance);
        $this->assertEquals('0.00', $payee->fresh()->balance);
    }

    /** @test */
    public function it_sends_notification_to_payee()
    {
        $this->mockSuccessfulAuthorization();

        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000, 'name' => 'João']);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0, 'email' => 'payee@example.com']);

        $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        Http::assertSent(function ($request) use ($payee) {
            return $request->url() === 'https://util.devi.tools/api/v1/notify' &&
                   $request['email'] === $payee->email;
        });
    }

    /** @test */
    public function it_does_not_fail_transfer_if_notification_fails()
    {
        // Mock com notificação falhando
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response(['status' => 'success'], 200),
            'https://util.devi.tools/api/v1/notify' => Http::response([], 500),
        ]);

        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('900.00', $payer->fresh()->balance);
        $this->assertEquals('100.00', $payee->fresh()->balance);
    }

    /** @test */
    public function it_returns_correct_response_structure()
    {
        $this->mockSuccessfulAuthorization();

        $payer = User::factory()->create(['type' => 'common', 'balance' => 1000]);
        $payee = User::factory()->create(['type' => 'common', 'balance' => 0]);

        $response = $this->postJson('/api/transfer', [
            'value' => 100,
            'payer' => $payer->id,
            'payee' => $payee->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'transaction_id',
                    'payer' => ['id', 'name', 'balance'],
                    'payee' => ['id', 'name', 'balance'],
                    'value',
                    'status',
                    'created_at',
                ]
            ]);
    }
}
