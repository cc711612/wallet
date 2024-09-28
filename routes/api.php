<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Apis\Auth\LogoutController;
use App\Http\Controllers\Apis\Auth\LoginController;
use App\Http\Controllers\Apis\Wallets\WalletController;
use App\Http\Controllers\Apis\Wallets\WalletDetailController;
use App\Http\Controllers\Apis\Wallets\WalletUserController;
use App\Http\Controllers\Apis\Auth\RegisterController;
use App\Http\Controllers\Apis\Wallets\Auth\WalletRegisterController;
use App\Http\Controllers\Apis\Wallets\Auth\WalletLoginController;
use App\Http\Controllers\Apis\Logs\LineController;
use App\Http\Controllers\Apis\Logs\FrontLogController;
use App\Http\Controllers\Apis\Options\OptionController;

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

Route::group(['middleware' => [], 'as' => 'api.',], function () {
    # 登入相關
    Route::group(['as' => 'auth.', 'namespace' => 'Auth', 'prefix' => 'auth'], function () {
        Route::name('thirdParty.line')->post('/thirdParty/line', function () {
            return redirect('https://ezionic.usongrat.tw/auth/thirdParty/return?provider=line&token=123123');
        });
        Route::name("login")->post("/login", [LoginController::class, 'login']);
        Route::name("cache")->match(['get', 'post'], "/cache", [LoginController::class, 'cache']);
        Route::name("register")->post("/register", [RegisterController::class, 'register']);
        Route::group(['as' => 'thirdParty', 'prefix' => 'thirdParty'], function () {
            Route::name('login')->post('/login', [LoginController::class, 'thirdPartyLogin']);
        });
    });
    # 選項
    Route::group(['as' => 'option.', 'namespace' => 'Options', 'prefix' => '/option'], function () {
        Route::name("exchangeRate")->get("/exchangeRate", [OptionController::class, 'exchangeRate']);
        Route::name("category")->get("/category", [OptionController::class, 'category']);
    });
    # 帳本成員
    Route::group(['as' => 'wallet.', 'prefix' => '/wallet'], function () {
        Route::name("user")->match(['get', 'post'], "/user", [WalletUserController::class, 'index']);
        # 登入
        Route::group(['as' => 'auth.', 'prefix' => '/auth'], function () {
            Route::name("login")->post("/login", [WalletLoginController::class, 'login']);
            Route::name("login.token")->post("/login/token", [WalletLoginController::class, 'token']);
            Route::name("register")->post("/register", [WalletRegisterController::class, 'register']);
            Route::name("register.batch")->post("/register/batch", [WalletRegisterController::class, 'registerBatch']);
        });
    });
    # Webhook
    Route::group(['as' => 'webhook.', 'prefix' => '/webhook'], function () {
        Route::group(['as' => 'line.', 'prefix' => '/line'], function () {
            Route::name("store")->any("/", [LineController::class, 'store']);
            Route::name("notify")->any("/notify", [LineController::class, 'notify']);
            Route::name("notifyBind")->any("/notifyBind", [LineController::class, 'notifyBind'])
                ->middleware(['VerifyApi']);
            Route::name("notifyToken")->any("/notifyToken", [LineController::class, 'notifyToken'])
                ->middleware(['VerifyApi']);
            Route::name("notifySendMessage")->any("/notifySendMessage", [LineController::class, 'notifySendMessage'])
                ->middleware(['VerifyApi']);
        });
    });
    # Log
    Route::group(['as' => 'log.', 'prefix' => '/log'], function () {
        # Front
        Route::group(['as' => 'front.', 'prefix' => '/front'], function () {
            Route::name("info")->post("/normal", [FrontLogController::class, 'normal']);
            Route::name("critical")->post("/serious", [FrontLogController::class, 'serious']);
        });
    });
    # 需要member_token的
    Route::group(['middleware' => ['VerifyApi']], function () {
        # 登出相關
        Route::group(['as' => 'auth.', 'namespace' => 'Auth', 'prefix' => 'auth'], function () {
            Route::name("logout")->post("/logout", [LogoutController::class, 'logout']);
        });
        Route::group(['as' => 'wallet.', 'prefix' => 'wallet'], function () {
            Route::name("index")->post("/list", [WalletController::class, 'index']);
            Route::name("get")->get("/", [WalletController::class, 'index']);
            # 綁定訪客帳本
            Route::name("bind")->post("/bind", [WalletController::class, 'bind']);
        });
        # 帳本
        Route::resource('wallet', WalletController::class)->only(['store', 'update', 'destroy']);
    });
    # 需要wallet_member_token的
    Route::group(['middleware' => ['VerifyWalletMemberApi']], function () {
        Route::group(['as' => 'wallet.', 'prefix' => 'wallet'], function () {
            Route::group(['as' => 'user.', 'prefix' => 'user'], function () {
                Route::name("update")->put("{wallet_users_id}", [WalletUserController::class, 'update']);
            });
            # 帳本明細
            Route::group(['prefix' => '{wallet}'], function () {
                Route::group(['prefix' => 'detail', 'as' => 'detail.'], function () {
                    Route::name("index")->post("/list", [WalletDetailController::class, 'index']);
                    Route::name("get")->get("/", [WalletDetailController::class, 'index']);
                    Route::name("show")->match(['get', 'post'], "/{detail}", [WalletDetailController::class, 'show']);
                    Route::name("checkout")->put("/checkout", [WalletDetailController::class, 'checkout']);
                    Route::name("uncheckout")->put("/undo_checkout", [WalletDetailController::class, 'uncheckout']);
                });
                Route::name("calculation")->post("/calculation", [WalletController::class, 'calculation']);
                Route::resource('detail', WalletDetailController::class)->only(['store', 'update', 'destroy']);
                # 帳本成員
                Route::group(['as' => 'user.', 'prefix' => 'user'], function () {
                    Route::name("destroy")->delete("/{wallet_user_id}", [WalletUserController::class, 'destroy']);
                });
            });
        });
    });
});
Route::fallback(function () {
    return response([
        'code'    => 404,
        'status'  => false,
        'message' => '不支援此方法',
    ], 404);
});
