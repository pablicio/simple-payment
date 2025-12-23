<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     * Listar todas as transações
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['payer:id,name,email', 'payee:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filtros opcionais
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payer_id')) {
            $query->where('payer_id', $request->payer_id);
        }

        if ($request->has('payee_id')) {
            $query->where('payee_id', $request->payee_id);
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ]
        ]);
    }

    /**
     * GET /api/transactions/{id}
     * Mostrar uma transação específica
     */
    public function show(int $id)
    {
        try {
            $transaction = Transaction::with(['payer:id,name,email', 'payee:id,name,email'])
                ->findOrFail($id);

            return response()->json([
                'data' => [
                    'id' => $transaction->id,
                    'payer' => [
                        'id' => $transaction->payer->id,
                        'name' => $transaction->payer->name,
                        'email' => $transaction->payer->email,
                    ],
                    'payee' => [
                        'id' => $transaction->payee->id,
                        'name' => $transaction->payee->name,
                        'email' => $transaction->payee->email,
                    ],
                    'value' => $transaction->value,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->toIso8601String(),
                    'updated_at' => $transaction->updated_at->toIso8601String(),
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Transaction not found',
            ], 404);
        }
    }
}
