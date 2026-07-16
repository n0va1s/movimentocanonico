<?php

use App\Http\Controllers\Api\PessoaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('eventos/{evento}/pessoas', [PessoaController::class, 'index']);
    Route::get('eventos/{evento}/pessoas/{pessoa}', [PessoaController::class, 'show']);
});
