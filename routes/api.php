<?php

use App\Http\Controllers\API\UserAuthentication;
use App\Http\Controllers\Approvals\ApprovalBase;
use App\Http\Controllers\Approvals\ApprovalProject;
use App\Http\Controllers\Master\Employee;
use App\Http\Controllers\Master\Setup_Company;
use App\Http\Controllers\Master\Structure;
use App\Http\Controllers\Master\TransactionType;
use App\Http\Controllers\Transaction\Project;
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


Route::group(['prefix' => 'auth', 'middleware' => 'cors'], function () {
    Route::get('login', [UserAuthentication::class, 'AuthenticationNeeded'])->name('login'); /* When not authenticate user access */
    Route::post('register', [UserAuthentication::class, 'register']);
    Route::post('create_new_password', [UserAuthentication::class, 'create_newPassword']);
    Route::post('signin', [UserAuthentication::class, 'login']);

    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('signout/{tokensId}', [UserAuthentication::class, 'logout']);
        Route::get('users', [UserAuthentication::class, 'index']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('master')->group(function () {
        /* Company Setup */
        Route::prefix('company')->group(function () {
            Route::get('index', [Setup_Company::class, 'index_Company']);
            Route::post('create', [Setup_Company::class, 'create_Company']);
            Route::get('select/option', [Setup_Company::class, 'option_Company']);
        });
        /* Departement Setup */
        Route::prefix('departement')->group(function () {
            Route::get('index', [Setup_Company::class, 'index_Departement']);
            Route::post('create', [Setup_Company::class, 'create_Departement']);
            Route::get('select/option', [Setup_Company::class, 'option_Departement']);
        });
        /* Position Setup */
        Route::prefix('position')->group(function () {
            Route::get('index', [Setup_Company::class, 'index_JobPosition']);
            Route::post('create', [Setup_Company::class, 'create_JobPosition']);
            Route::get('select/option', [Setup_Company::class, 'option_JobPosition']);
        });
        /* Employee */
        Route::prefix('employee')->group(function () {
            Route::get('index', [Employee::class, 'index_Employee']);
            Route::post('create', [Employee::class, 'create_Employee']);
        });
        /* Structure */
        Route::prefix('structure')->group(function () {
            Route::get('/', [Structure::class, 'index_Structure']);
            Route::post('detail', [Structure::class, 'get_structure']);
            Route::post('create', [Structure::class, 'create_msStructure']);
            Route::post('detail/create', [Structure::class, 'create_dtStructure']);
            Route::post('get/employee', [Employee::class, 'employee_onPosition'])->withoutMiddleware('auth:api');
            // ->withoutMiddleware('auth:api');
        });

        /* Other */
        Route::prefix('transtype')->group(function () {
            Route::get('projectType/{id}', [TransactionType::class, 'show_projectType']);
            Route::get('projectSubType/{id}', [TransactionType::class, 'show_projectSubType']);
            Route::get('projectSubDtType/{id}', [TransactionType::class, 'show_projectSubDtType']);
        });
        Route::prefix('approval_code')->group(function () {
            Route::get('list', [ApprovalBase::class, '_apprCode']);
        });
    });

    Route::prefix('transaction')->group(function () {
        Route::prefix('project')->group(function () {
            Route::apiResource('/', Project::class);
            Route::post('newTransaction', [Project::class, 'index_newTransaction']);
            Route::post('onProgress', [Project::class, 'index_empTransProgress']);


            Route::get('header/{uuid}', [Project::class, 'index_transByHeader']);
            Route::get('detailByHeader/{uuid}', [Project::class, 'index_detailByHeader']);
            Route::get('EditData/{uuid}', [Project::class, 'modalEditData']);
            Route::post('save/{uuid}', [Project::class, 'create']);
            Route::post('delete/{uuid}', [Project::class, 'delete']);

            Route::post('createHeader', [Project::class, 'create_header']);
            Route::post('createDetail', [Project::class, 'create_detail']);
            Route::post('updateHeader/{uuid}', [Project::class, 'update_header']);

            Route::prefix('approval')->group(function () {
                Route::post('request/{uuid}', [ApprovalProject::class, 'createUpdateApproval']);
                Route::post('request/btAction/{uuid}', [ApprovalProject::class, 'createApproval']);

                Route::post('/', [ApprovalProject::class, 'index_approvalTable']);
                Route::post('action/{uuid}', [ApprovalProject::class, 'actionApproval']);
            });
        });
    });
});
