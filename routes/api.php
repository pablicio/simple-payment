<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TransactionController;

// Rotas de usuários
Route::apiResource('users', UserController::class);

// Rotas adicionais de usuários
Route::get('users/{id}/balance', [UserController::class, 'balance']);

// Rota de transferência com rate limiting (10 requisições por minuto)
Route::post('transfer', [TransferController::class, 'transfer'])
    ->middleware('throttle:10,1');

// Rotas de transações (apenas leitura)
Route::get('transactions', [TransactionController::class, 'index']);
Route::get('transactions/{id}', [TransactionController::class, 'show']);

// Rotas adicionais de transações
Route::get('transactions/user/{userId}/stats', [TransactionController::class, 'userStats']);
