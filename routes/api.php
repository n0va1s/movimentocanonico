<?php

use App\Http\Controllers\Api\ExportacaoDadosController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('eventos/{id_evento}/pessoas', [ExportacaoDadosController::class, 'index']);
    Route::get('eventos/{id_evento}/pessoas/{id_pessoa}', [ExportacaoDadosController::class, 'show']);
});
