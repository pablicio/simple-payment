<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Listar todos os usuários
     */
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'document', 'type', 'balance')
            ->get();

        return response()->json([
            'data' => $users
        ]);
    }

    /**
     * POST /api/users
     * Criar novo usuário
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'document' => 'required|string|unique:users,document',
                'password' => 'required|string|min:6',
                'type' => ['required', Rule::in([User::TYPE_COMMON, User::TYPE_MERCHANT])],
                'balance' => 'nullable|numeric|min:0',
            ]);

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

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
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
     * PUT /api/users/{id}
     * Atualizar um usuário
     */
    public function update(Request $request, int $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
                'document' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
                'password' => 'sometimes|string|min:6',
                'type' => ['sometimes', Rule::in([User::TYPE_COMMON, User::TYPE_MERCHANT])],
                'balance' => 'sometimes|numeric|min:0',
            ]);

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

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
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
            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
    }
}
