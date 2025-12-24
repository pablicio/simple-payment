<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TransactionController;

// Rotas de usuários com rate limiting
// Criação de usuários: 5 requisições por minuto
Route::post('users', [UserController::class, 'store'])
    ->middleware('throttle:5,1');

// Outras operações de usuários: 60 requisições por minuto
Route::middleware('throttle:60,1')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::get('users/{id}/balance', [UserController::class, 'balance']);
});

// Rota de transferência com rate limiting (10 requisições por minuto)
Route::post('transfer', [TransferController::class, 'transfer'])
    ->middleware('throttle:10,1');

// Rotas de transações (apenas leitura)
Route::get('transactions', [TransactionController::class, 'index']);
Route::get('transactions/{id}', [TransactionController::class, 'show']);

// Rotas adicionais de transações
Route::get('transactions/user/{userId}/stats', [TransactionController::class, 'userStats']);
