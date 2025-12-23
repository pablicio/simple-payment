<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Services\TransferService;

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
     * Realizar transferência entre usuários
     * 
     * @param TransferRequest $request Validação automática via Form Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transfer(TransferRequest $request)
    {
        try {
            // Os dados já vêm validados pelo TransferRequest
            $validated = $request->validated();

            // Executar transferência
            $transaction = $this->transferService->transfer(
                payerId: $validated['payer'],
                payeeId: $validated['payee'],
                amount: (float) $validated['value']
            );

            // Retornar sucesso com dados da transação
            return response()->json([
                'message' => 'Transfer completed successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'payer' => [
                        'id' => $transaction->payer->id,
                        'name' => $transaction->payer->name,
                        'balance' => $transaction->payer->balance,
                    ],
                    'payee' => [
                        'id' => $transaction->payee->id,
                        'name' => $transaction->payee->name,
                        'balance' => $transaction->payee->balance,
                    ],
                    'value' => $transaction->value,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
