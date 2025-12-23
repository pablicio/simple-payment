<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;

Route::get('/', function () {
    return view('welcome');
});
