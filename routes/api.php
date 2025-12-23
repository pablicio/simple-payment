<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransferController;

// Rotas de usuários
Route::apiResource('users', UserController::class);

// Rota de transferência
Route::post('transfer', [TransferController::class, 'transfer']);
