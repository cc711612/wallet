<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mains\Auth\LoginController;
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

Route::get('/', function () {
    return redirect(config('services.easysplit.domain'));
});
Route::group(['as' => 'auth.', 'namespace' => 'Auth', 'prefix' => 'auth'], function () {
    Route::name("login")->get("/login", [LoginController::class, 'login']);
    Route::group(['as' => 'thirdParty', 'prefix' => 'thirdParty'], function () {
        Route::group(['as' => 'line.', 'prefix' => 'line'], function () {
            Route::name("login")->get("/login", [LoginController::class, 'lineLogin']);
            Route::name("return")->get("/return", [LoginController::class, 'lineReturn']);
        });
    });
});
