<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shopkeeper;
use App\Services\TransferService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    protected TransferService $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * POST /api/transfer
     * Executar transferência entre usuários
     */
    public function transfer(Request $request)
    {
        try {
            // 1. Validar request
            $validated = $request->validate([
                'value' => 'required|numeric|min:0.01',
                'payer' => 'required|integer|exists:users,id',
                'payee' => 'required|integer',
                'payee_type' => 'nullable|string|in:user,shopkeeper',
            ]);

            // 2. Executar transferência
            $transaction = $this->transferService->transfer(
                payerId: $validated['payer'],
                payeeId: $validated['payee'],
                amount: (float) $validated['value'],
                payeeType: $this->mapPayeeType($validated['payee_type'] ?? null, $validated['payee'])
            );

            // 3. Retornar sucesso
            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'payer' => $transaction->payer->only('id', 'name', 'balance'),
                    'payee' => $transaction->payee->only('id', 'name', 'balance'),
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $this->getHttpStatus($e));
        }
    }

    /**
     * Mapear tipo de recebedor
     */
    private function mapPayeeType(?string $type, int $payeeId): string
    {
        // Se informado o tipo, validar
        if ($type === 'shopkeeper') {
            return Shopkeeper::class;
        }

        if ($type === 'user') {
            return User::class;
        }

        // Se não informado, tentar descobrir automaticamente
        if (User::find($payeeId)) {
            return User::class;
        }

        return Shopkeeper::class;
    }

    /**
     * Retornar status HTTP baseado na exception
     */
    private function getHttpStatus(\Exception $e): int
    {
        return match (true) {
            str_contains($e->getMessage(), 'not found') => 404,
            str_contains($e->getMessage(), 'not authorized') => 403,
            str_contains($e->getMessage(), 'cannot') => 403,
            str_contains($e->getMessage(), 'Insufficient') => 400,
            default => 500,
        };
    }
}
