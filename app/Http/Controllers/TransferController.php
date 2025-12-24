<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransferResource;
use App\Services\TransferService;
use Illuminate\Support\Facades\Log;

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
        $requestId = $request->header('X-Request-ID');

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
            return (new TransferResource($transaction))
                ->response()
                ->setStatusCode(201)
                ->header('X-Request-ID', $requestId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Transfer failed - User not found', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'User not found',
                'error' => 'One or more users do not exist',
            ], 404)->header('X-Request-ID', $requestId);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Transfer failed - Database error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'message' => 'Database error occurred',
                'error' => 'Unable to process transfer. Please try again later.',
            ], 500)->header('X-Request-ID', $requestId);

        } catch (\Exception $e) {
            $statusCode = 400;
            $message = $e->getMessage();

            // Determinar código de status baseado na mensagem de erro
            if (str_contains($message, 'not authorized')) {
                $statusCode = 403;
            } elseif (str_contains($message, 'Insufficient balance')) {
                $statusCode = 422;
            }

            Log::warning('Transfer failed - Business rule', [
                'request_id' => $requestId,
                'error' => $message,
                'status_code' => $statusCode,
            ]);

            return response()->json([
                'message' => $message,
            ], $statusCode)->header('X-Request-ID', $requestId);
        }
    }
}
