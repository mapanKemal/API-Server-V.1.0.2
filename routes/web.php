<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::middleware('auth:api')->get('/', function () {
//     return ['Laravel' => app()->version()];
// });
Route::get('/', function () {
    return response([['Laravel' => app()->version()], ['Time' => date('Y M d, H:i:s')]]);
});

require __DIR__ . '/auth.php';
