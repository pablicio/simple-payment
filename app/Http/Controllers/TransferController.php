<?php

namespace App\Http\Controllers;

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
     * 
     * Body esperado:
     * {
     *   "value": 100.00,
     *   "payer": 1,
     *   "payee": 2
     * }
     */
    public function transfer(Request $request)
    {
        try {
            // Validar request
            $validated = $request->validate([
                'value' => 'required|numeric|min:0.01',
                'payer' => 'required|integer|exists:users,id',
                'payee' => 'required|integer|exists:users,id',
            ]);

            // Executar transferência
            $transaction = $this->transferService->transfer(
                payerId: $validated['payer'],
                payeeId: $validated['payee'],
                amount: (float) $validated['value']
            );

            // Retornar sucesso
            return response()->json([
                'message' => 'Transfer completed successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'payer' => $transaction->payer->only('id', 'name', 'balance'),
                    'payee' => $transaction->payee->only('id', 'name', 'balance'),
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
