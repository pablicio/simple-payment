<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct()
    {
        // Rate limiting para criação de usuários: 5 por minuto
        $this->middleware('throttle:5,1')->only('store');
        
        // Rate limiting para outras operações: 60 por minuto
        $this->middleware('throttle:60,1')->except('store');
    }

    /**
     * GET /api/users
     * Listar todos os usuários com cache
     */
    public function index()
    {
        $requestId = request()->header('X-Request-ID');

        try {
            // Cache da listagem completa por 10 minutos
            $cacheKey = 'users:all';
            $cacheTTL = now()->addMinutes(10);

            $cacheHit = Cache::has($cacheKey);

            $users = Cache::remember($cacheKey, $cacheTTL, function () {
                return User::select('id', 'name', 'email', 'document', 'type', 'balance')
                    ->orderBy('id')
                    ->get();
            });

            Log::debug('Users list retrieved', [
                'request_id' => $requestId,
                'count' => $users->count(),
                'cache_hit' => $cacheHit,
            ]);

            return new UserCollection($users);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve users', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error retrieving users',
            ], 500);
        }
    }

    /**
     * POST /api/users
     * Criar novo usuário
     * 
     * @param StoreUserRequest $request Validação automática via Form Request
     */
    public function store(StoreUserRequest $request)
    {
        $requestId = $request->header('X-Request-ID');

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

            Log::info('User created successfully', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'user_type' => $user->type,
            ]);

            return response()->json([
                'message' => 'User created successfully',
                'data' => new UserResource($user)
            ], 201)->header('X-Request-ID', $requestId);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Failed to create user - Database error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Verificar se é erro de duplicidade
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'User already exists',
                    'error' => 'Email or document already registered',
                ], 422)->header('X-Request-ID', $requestId);
            }

            return response()->json([
                'message' => 'Error creating user',
            ], 500)->header('X-Request-ID', $requestId);

        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error creating user',
            ], 500)->header('X-Request-ID', $requestId);
        }
    }

    /**
     * GET /api/users/{id}
     * Mostrar um usuário específico com cache
     */
    public function show(int $id)
    {
        $requestId = request()->header('X-Request-ID');

        try {
            // Cache individual por usuário (15 minutos)
            $cacheKey = "user:{$id}";
            $cacheTTL = now()->addMinutes(15);

            $cacheHit = Cache::has($cacheKey);

            $user = Cache::remember($cacheKey, $cacheTTL, function () use ($id) {
                return User::findOrFail($id);
            });

            Log::debug('User retrieved', [
                'request_id' => $requestId,
                'user_id' => $id,
                'cache_hit' => $cacheHit,
            ]);

            return response()->json([
                'data' => new UserResource($user)
            ])->header('X-Request-ID', $requestId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('User not found', [
                'request_id' => $requestId,
                'user_id' => $id,
            ]);

            return response()->json([
                'message' => 'User not found',
            ], 404)->header('X-Request-ID', $requestId);
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
        $requestId = $request->header('X-Request-ID');

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

            Log::info('User updated successfully', [
                'request_id' => $requestId,
                'user_id' => $id,
                'updated_fields' => array_keys($validated),
            ]);

            return response()->json([
                'message' => 'User updated successfully',
                'data' => new UserResource($user)
            ])->header('X-Request-ID', $requestId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('User not found for update', [
                'request_id' => $requestId,
                'user_id' => $id,
            ]);

            return response()->json([
                'message' => 'User not found',
            ], 404)->header('X-Request-ID', $requestId);

        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'request_id' => $requestId,
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error updating user',
            ], 500)->header('X-Request-ID', $requestId);
        }
    }

    /**
     * DELETE /api/users/{id}
     * Deletar um usuário
     */
    public function destroy(int $id)
    {
        $requestId = request()->header('X-Request-ID');

        try {
            $user = User::findOrFail($id);
            $userName = $user->name;
            
            // Verificar se o usuário tem transações
            $hasTransactions = $user->sentTransactions()->exists() 
                || $user->receivedTransactions()->exists();

            if ($hasTransactions) {
                Log::warning('Attempted to delete user with transactions', [
                    'request_id' => $requestId,
                    'user_id' => $id,
                ]);

                return response()->json([
                    'message' => 'Cannot delete user with existing transactions',
                ], 422)->header('X-Request-ID', $requestId);
            }

            $user->delete();

            // Invalidar cache após exclusão
            $this->invalidateCache($id);

            Log::info('User deleted successfully', [
                'request_id' => $requestId,
                'user_id' => $id,
                'user_name' => $userName,
            ]);

            return response()->json([
                'message' => "User '{$userName}' deleted successfully",
            ])->header('X-Request-ID', $requestId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('User not found for deletion', [
                'request_id' => $requestId,
                'user_id' => $id,
            ]);

            return response()->json([
                'message' => 'User not found',
            ], 404)->header('X-Request-ID', $requestId);

        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'request_id' => $requestId,
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error deleting user',
            ], 500)->header('X-Request-ID', $requestId);
        }
    }

    /**
     * GET /api/users/{id}/balance
     * Obter saldo atual do usuário com cache curto
     */
    public function balance(int $id)
    {
        $requestId = request()->header('X-Request-ID');

        try {
            // Cache de saldo com TTL curto (2 minutos) - dados financeiros mudam frequentemente
            $cacheKey = "user:{$id}:balance";
            $cacheTTL = now()->addMinutes(2);

            $cacheHit = Cache::has($cacheKey);

            $balance = Cache::remember($cacheKey, $cacheTTL, function () use ($id) {
                $user = User::findOrFail($id);
                return $user->balance;
            });

            Log::debug('User balance retrieved', [
                'request_id' => $requestId,
                'user_id' => $id,
                'cache_hit' => $cacheHit,
            ]);

            return response()->json([
                'data' => [
                    'user_id' => $id,
                    'balance' => $balance,
                ]
            ])->header('X-Request-ID', $requestId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('User not found for balance check', [
                'request_id' => $requestId,
                'user_id' => $id,
            ]);

            return response()->json([
                'message' => 'User not found',
            ], 404)->header('X-Request-ID', $requestId);
        }
    }

    /**
     * Invalidar cache de usuários quando houver mudanças
     */
    private function invalidateCache(?int $userId = null): void
    {
        $invalidatedKeys = [];

        // Invalidar cache da listagem geral
        if (Cache::forget('users:all')) {
            $invalidatedKeys[] = 'users:all';
        }

        // Invalidar cache específico do usuário
        if ($userId) {
            $userKeys = ["user:{$userId}", "user:{$userId}:balance"];
            
            foreach ($userKeys as $key) {
                if (Cache::forget($key)) {
                    $invalidatedKeys[] = $key;
                }
            }
        }

        if (!empty($invalidatedKeys)) {
            Log::debug('User cache invalidated', [
                'keys' => $invalidatedKeys,
                'count' => count($invalidatedKeys),
            ]);
        }
    }
}
