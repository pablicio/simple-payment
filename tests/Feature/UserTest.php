<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_common_user_successfully()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'document' => '12345678901',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'common',
            'balance' => 1000,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User created successfully',
                'data' => [
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                    'document' => '12345678901',
                    'type' => 'common',
                    'balance' => '1000.00',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@example.com',
            'type' => 'common',
        ]);
    }

    /** @test */
    public function it_can_create_a_merchant_user_successfully()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Loja ABC',
            'email' => 'loja@example.com',
            'document' => '12345678000199',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'merchant',
            'balance' => 5000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email', 'document', 'type', 'balance']
            ]);

        $this->assertEquals('merchant', $response->json('data.type'));
    }

    /** @test */
    public function it_requires_all_mandatory_fields()
    {
        $response = $this->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'document', 'password', 'type']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'invalid-email',
            'document' => '12345678901',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'common',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_unique_email()
    {
        User::factory()->create(['email' => 'joao@example.com']);

        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'document' => '12345678901',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'common',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_unique_document()
    {
        User::factory()->create(['document' => '12345678901']);

        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'document' => '12345678901',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'common',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document']);
    }

    /** @test */
    public function it_validates_password_minimum_length()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'document' => '12345678901',
            'password' => '123',
            'password_confirmation' => '123',
            'type' => 'common',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_validates_user_type_must_be_common_or_merchant()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'document' => '12345678901',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_hashes_password_before_saving()
    {
        $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'document' => '12345678901',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'common',
        ]);

        $user = User::where('email', 'joao@example.com')->first();
        
        $this->assertNotNull($user);
        $this->assertNotEquals('Senha@123', $user->password);
        $this->assertTrue(password_verify('Senha@123', $user->password));
    }

    /** @test */
    public function it_sets_default_balance_to_zero_if_not_provided()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'document' => '12345678901',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'type' => 'common',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('0.00', $response->json('data.balance'));
    }
}
