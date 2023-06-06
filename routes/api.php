<?php

use App\Http\Controllers\API\UserAuthentication;
use App\Http\Controllers\Master\Setup_Company;
use App\Http\Controllers\Master\TransactionType;
use App\Http\Controllers\Transaction\Project;
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
    Route::prefix('master')->group(function () {
        /* Company Setup */
        Route::prefix('company')->group(function () {
            Route::get('/index', [Setup_Company::class, 'index_Company']);
            Route::post('/create', [Setup_Company::class, 'create_Company']);
        });
        Route::prefix('departement')->group(function () {
            Route::get('/index', [Setup_Company::class, 'index_Departement']);
            Route::post('/create', [Setup_Company::class, 'create_Departement']);
        });
        Route::prefix('position')->group(function () {
            Route::get('/index', [Setup_Company::class, 'index_JobPosition']);
            Route::post('/create', [Setup_Company::class, 'create_JobPosition']);
        });

        /* Other */
        Route::prefix('transtype')->group(function () {
            Route::get('/projectType/{id}', [TransactionType::class, 'show_projectType']);
            Route::get('/projectSubType/{id}', [TransactionType::class, 'show_projectSubType']);
            Route::get('/projectSubDtType/{id}', [TransactionType::class, 'show_projectSubDtType']);
        });
    });

    Route::prefix('transaction')->group(function () {
        Route::prefix('project')->group(function () {
            Route::apiResource('/', Project::class);
            Route::get('/newTransaction', [Project::class, 'index_newTransaction']);
            // Route::get('/transByHeader/{uuid}', [Project::class, 'index_transByHeader']);
            Route::get('/EditData/{uuid}', [Project::class, 'modalEditData']);
            Route::post('/createHeader', [Project::class, 'create_header']);
            Route::post('/updateHeader/{uuid}', [Project::class, 'update_header']);
            Route::post('/createDetail', [Project::class, 'create_detail']);
        });
    });
});
