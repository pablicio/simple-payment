<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Listar todos os usuários
     */
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'document', 'type', 'balance')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $users
        ]);
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

            return response()->json([
                'message' => 'User created successfully',
                'data' => $user->only('id', 'name', 'email', 'document', 'type', 'balance')
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
     * Mostrar um usuário específico
     */
    public function show(int $id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'data' => $user->only('id', 'name', 'email', 'document', 'type', 'balance')
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

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $user->only('id', 'name', 'email', 'document', 'type', 'balance')
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
}
