<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Listar todos os usuários com cache
     */
    public function index()
    {
        // Cache da listagem completa por 10 minutos
        $cacheKey = 'users:all';
        $cacheTTL = now()->addMinutes(10);

        $users = Cache::remember($cacheKey, $cacheTTL, function () {
            return User::select('id', 'name', 'email', 'document', 'type', 'balance')
                ->orderBy('id')
                ->get();
        });

        return new UserCollection($users);
    }

    /**
     * POST /api/users
     * Criar novo usuário
     * 
     * @param StoreUserRequest $request Validação automática via Form Request
     */
    public function store(StoreUserRequest $request)
    {
        try {
            // Dados já validados pelo StoreUserRequest
            $validated = $request->validated();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'document' => $validated['document'],
                'password' => Hash::make($validated['password']),
                'type' => $validated['type'],
                'balance' => $validated['balance'] ?? 0,
            ]);

            // Invalidar cache após criação
            $this->invalidateCache();

            return response()->json([
                'message' => 'User created successfully',
                'data' => new UserResource($user)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/{id}
     * Mostrar um usuário específico com cache
     */
    public function show(int $id)
    {
        try {
            // Cache individual por usuário (15 minutos)
            $cacheKey = "user:{$id}";
            $cacheTTL = now()->addMinutes(15);

            $user = Cache::remember($cacheKey, $cacheTTL, function () use ($id) {
                return User::findOrFail($id);
            });

            return response()->json([
                'data' => new UserResource($user)
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * PUT/PATCH /api/users/{id}
     * Atualizar um usuário
     * 
     * @param UpdateUserRequest $request Validação automática via Form Request
     */
    public function update(UpdateUserRequest $request, int $id)
    {
        try {
            $user = User::findOrFail($id);

            // Dados já validados pelo UpdateUserRequest
            $validated = $request->validated();

            // Hash da senha se fornecida
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            // Invalidar cache após atualização
            $this->invalidateCache($id);

            return response()->json([
                'message' => 'User updated successfully',
                'data' => new UserResource($user)
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/users/{id}
     * Deletar um usuário
     */
    public function destroy(int $id)
    {
        try {
            $user = User::findOrFail($id);
            $userName = $user->name;
            
            $user->delete();

            // Invalidar cache após exclusão
            $this->invalidateCache($id);

            return response()->json([
                'message' => "User '{$userName}' deleted successfully",
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/{id}/balance
     * Obter saldo atual do usuário com cache curto
     */
    public function balance(int $id)
    {
        try {
            // Cache de saldo com TTL curto (2 minutos) - dados financeiros mudam frequentemente
            $cacheKey = "user:{$id}:balance";
            $cacheTTL = now()->addMinutes(2);

            $balance = Cache::remember($cacheKey, $cacheTTL, function () use ($id) {
                $user = User::findOrFail($id);
                return $user->balance;
            });

            return response()->json([
                'data' => [
                    'user_id' => $id,
                    'balance' => $balance,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Invalidar cache de usuários quando houver mudanças
     */
    private function invalidateCache(?int $userId = null): void
    {
        // Invalidar cache da listagem geral
        Cache::forget('users:all');

        // Invalidar cache específico do usuário
        if ($userId) {
            Cache::forget("user:{$userId}");
            Cache::forget("user:{$userId}:balance");
        }
    }
}
