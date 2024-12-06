<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\PurchaseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Route::get('/', function () {
//    return response()->json([
//        'message' => 'API is working',
//        'status'  => 200
//    ]);
//});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
// 受保护的路由组
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('links', [LinkController::class, 'store']);
    Route::get('links', [LinkController::class, 'index']);
    Route::put('links/{id}', [LinkController::class, 'update']);
    Route::delete('links/{id}', [LinkController::class, 'destroy']);

    Route::post('purchase/{id}', [PurchaseController::class, 'purchase']);
    Route::get('purchase/{id}', [PurchaseController::class, 'purchase']);
    Route::get('purchase/status/{id}', [PurchaseController::class, 'status']);
});

// 短链接重定向路由（放在最后，避免路由冲突）
Route::get('/u', [LinkController::class, 'redirect']);
Route::post('/p', [PurchaseController::class, 'paymentSuccess']);
