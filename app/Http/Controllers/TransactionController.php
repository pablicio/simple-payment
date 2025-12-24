<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionCollection;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     * Listar todas as transações com cache inteligente
     */
    public function index(Request $request)
    {
        // Gerar chave de cache única baseada nos parâmetros da requisição
        $cacheKey = $this->generateCacheKey($request);
        
        // Tempo de cache: 5 minutos para listagens
        $cacheTTL = now()->addMinutes(5);

        $transactions = Cache::remember($cacheKey, $cacheTTL, function () use ($request) {
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

            // Filtro por período
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Paginação
            $perPage = $request->get('per_page', 15);
            return $query->paginate($perPage);
        });

        return new TransactionCollection($transactions);
    }

    /**
     * GET /api/transactions/{id}
     * Mostrar uma transação específica com cache
     */
    public function show(int $id)
    {
        try {
            // Cache individual por transação (10 minutos)
            $cacheKey = "transaction:{$id}";
            $cacheTTL = now()->addMinutes(10);

            $transaction = Cache::remember($cacheKey, $cacheTTL, function () use ($id) {
                return Transaction::with(['payer:id,name,email', 'payee:id,name,email'])
                    ->findOrFail($id);
            });

            return new TransactionResource($transaction);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Transaction not found',
            ], 404);
        }
    }

    /**
     * GET /api/transactions/user/{userId}/stats
     * Estatísticas de transações do usuário com cache
     */
    public function userStats(int $userId)
    {
        $cacheKey = "transaction:user:{$userId}:stats";
        $cacheTTL = now()->addMinutes(15);

        $stats = Cache::remember($cacheKey, $cacheTTL, function () use ($userId) {
            return [
                'total_sent' => Transaction::where('payer_id', $userId)
                    ->where('status', 'completed')
                    ->sum('value'),
                'total_received' => Transaction::where('payee_id', $userId)
                    ->where('status', 'completed')
                    ->sum('value'),
                'total_transactions_sent' => Transaction::where('payer_id', $userId)
                    ->count(),
                'total_transactions_received' => Transaction::where('payee_id', $userId)
                    ->count(),
                'pending_transactions' => Transaction::where(function ($query) use ($userId) {
                    $query->where('payer_id', $userId)
                        ->orWhere('payee_id', $userId);
                })
                    ->where('status', 'pending')
                    ->count(),
            ];
        });

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Invalidar cache de transações quando houver mudanças
     * Deve ser chamado após criar/atualizar transações
     */
    public function invalidateCache(?int $userId = null, ?int $transactionId = null): void
    {
        // Invalidar cache específico da transação
        if ($transactionId) {
            Cache::forget("transaction:{$transactionId}");
        }

        // Invalidar cache das estatísticas do usuário
        if ($userId) {
            Cache::forget("transaction:user:{$userId}:stats");
        }
        
        // Nota: Cache das listagens expira naturalmente (TTL de 5 minutos)
        // Para invalidação imediata de todas as listagens, seria necessário:
        // 1. Usar Redis/Memcached (que suportam tags)
        // 2. Rastrear manualmente todas as chaves de listagem
        // 3. Usar um prefixo de versão global
    }

    /**
     * Gerar chave de cache baseada nos parâmetros da requisição
     */
    private function generateCacheKey(Request $request): string
    {
        $params = [
            'status' => $request->get('status'),
            'payer_id' => $request->get('payer_id'),
            'payee_id' => $request->get('payee_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'per_page' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];

        // Remover valores nulos
        $params = array_filter($params, fn($value) => !is_null($value));

        return 'transactions:list:' . md5(json_encode($params));
    }
}
