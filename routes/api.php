<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TransactionController;

// Rotas de usuários
Route::apiResource('users', UserController::class);

// Rota de transferência
Route::post('transfer', [TransferController::class, 'transfer']);

// Rotas de transações (apenas leitura)
Route::get('transactions', [TransactionController::class, 'index']);
Route::get('transactions/{id}', [TransactionController::class, 'show']);
