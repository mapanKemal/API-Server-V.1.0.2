<?php

use App\Http\Controllers\API\UserAuthentication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::prefix('auth')->group(function () {
    Route::get('/login', [UserAuthentication::class, 'AuthenticationNeeded'])->name('login'); /* When not authenticate user access */
    Route::post('/register', [UserAuthentication::class, 'register']);
    Route::post('/signin', [UserAuthentication::class, 'login']);

    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('/signout/{tokensId}', [UserAuthentication::class, 'logout']);
        Route::get('/users', [UserAuthentication::class, 'index']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('transaction')->group(function () {
        Route::prefix('project')->group(function () {
            // Route::get('newTransaction', [])
        });
    });
});
