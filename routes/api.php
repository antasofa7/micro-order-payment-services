<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('orders', [OrderController::class, 'create']);
Route::get('orders', [OrderController::class, 'index']);

Route::post('webhook', [WebhookController::class, 'midtransHandler']);
