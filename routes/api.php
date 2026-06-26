<?php

use App\Http\Controllers\Api\MercadinhoController;
use Illuminate\Support\Facades\Route;

Route::get('mercadinho/pessoas/{id}', [MercadinhoController::class, 'buscarPessoa']);
