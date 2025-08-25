<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\PlanController;
use App\Models\Ingredient;

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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ingredients', function (Request $request) {
    return Ingredient::all();
});

Route::post('/plans/generate', [PlanController::class, 'generate'])
                ->middleware('auth:sanctum');

Route::post('/plans/save', [PlanController::class, 'store'])
                ->middleware('auth:sanctum');

Route::get('/plans', [PlanController::class, 'index'])
                ->middleware('auth:sanctum');

Route::delete('/plans/{id}', [PlanController::class, 'destroy'])
                ->middleware('auth:sanctum');

Route::post('/user-preferences', [UserPreferenceController::class, 'store'])
                ->middleware('auth:sanctum');
Route::get('/user-preferences', [UserPreferenceController::class, 'index'])
                ->middleware('auth:sanctum');
Route::post('/user-preferences/delete', [UserPreferenceController::class, 'destroy'])
                ->middleware('auth:sanctum');
Route::post('/user', [RegisteredUserController::class, 'edit'])
                ->middleware('auth:sanctum');

Route::post('/login', [RegisteredUserController::class, 'login'])
                ->middleware('guest');
Route::post('/register', [RegisteredUserController::class, 'store'])
                ->middleware('guest');
