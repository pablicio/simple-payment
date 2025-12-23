<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_all_users()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'document', 'type', 'balance']
                ]
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_empty_array_when_no_users_exist()
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    /** @test */
    public function it_can_show_a_specific_user()
    {
        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_user()
    {
        $response = $this->getJson('/api/users/9999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found',
            ]);
    }

    /** @test */
    public function it_can_update_user_name()
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User updated successfully',
                'data' => [
                    'name' => 'New Name',
                ]
            ]);

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "User '{$user->name}' deleted successfully",
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
