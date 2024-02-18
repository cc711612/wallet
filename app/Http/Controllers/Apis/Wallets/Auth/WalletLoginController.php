<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/19 下午 02:53
 */

namespace App\Http\Controllers\Apis\Wallets\Auth;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Http\Requesters\Apis\Wallets\Auth\LoginRequest;
use App\Http\Validators\Apis\Wallets\Auth\LoginValidator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Arr;
use App\Traits\Wallets\Auth\WalletUserAuthLoginTrait;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Models\Wallets\Databases\Services\WalletUserApiService;
use App\Http\Requesters\Apis\Wallets\Auth\LoginTokenRequest;
use App\Http\Validators\Apis\Wallets\Auth\LoginTokenValidator;
use Firebase\JWT\JWT;

/**
 * Class WalletLoginController
 *
 * @package App\Http\Controllers\Apis\Wallets\Auth
 * @Author: Roy
 * @DateTime: 2022/6/21 下午 12:08
 */
class WalletLoginController extends Controller
{
    use WalletUserAuthLoginTrait;

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/6/20 下午 03:16
     */
    public function login(Request $request)
    {
        $requester = (new LoginRequest($request));

        $Wallet = (new WalletApiService())
            ->setRequest($requester->toArray())
            ->getWalletByCode();

        if (is_null($Wallet)) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => "此帳簿不存在",
            ]);
        }

        $requester->__set('wallets.id', is_null($Wallet) ? null : $Wallet->id);
        $requester->__set('wallet_users.wallet_id', is_null($Wallet) ? null : $Wallet->id);

        $Validate = (new LoginValidator($requester))->validate();
        if ($Validate->fails() === true) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => $Validate->errors()->first(),
            ]);
        }
        $UserEntity = (new WalletUserApiService())
            ->setRequest($requester->toArray())
            ->getWalletUserByNameAndWalletId();

        if (is_null($UserEntity)) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => "系統錯誤",
            ]);
        }
        if ($UserEntity->is_admin == 1) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => "管理者不得使用此方式登入",
            ]);
        }
        # set cache
        $this->setMemberTokenCache($UserEntity);
        $key = config('app.name');
        $payload = [
            'iss' => config('app.url'),
            'aud' => 'https://easysplit.usongrat.tw',
            'iat' => now()->timestamp,
            'exp' => now()->addMonth()->timestamp,
            'nbf' => now()->timestamp,
            'wallet_user' => [
                'id' => Crypt::encryptString($UserEntity->id),
                'name' => $UserEntity->name,
                'created_at' => $UserEntity->created_at,
                'updated_at' => $UserEntity->updated_at,
            ]
        ];

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => null,
            'data'    => [
                'id'           => Arr::get($UserEntity, 'id'),
                'name'         => Arr::get($UserEntity, 'name'),
                'wallet_id'    => Arr::get($UserEntity, 'wallet_id'),
                'member_token' => Arr::get($UserEntity, 'token'),
                'jwt'          => JWT::encode($payload, $key, 'HS256'),
                'wallet'       => [
                    'id'   => Arr::get($Wallet, 'id'),
                    'code' => Arr::get($Wallet, 'code'),
                ],
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/6/28 上午 05:33
     */
    public function token(Request $request)
    {
        $requester = (new LoginTokenRequest($request));

        $Wallet = (new WalletApiService())
            ->setRequest($requester->toArray())
            ->getWalletByCode();

        if (is_null($Wallet)) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => "此帳簿不存在",
            ]);
        }

        $requester->__set('wallets.id', is_null($Wallet) ? null : $Wallet->id);
        $requester->__set('wallet_users.wallet_id', is_null($Wallet) ? null : $Wallet->id);

        $Validate = (new LoginTokenValidator($requester))->validate();
        if ($Validate->fails() === true) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => $Validate->errors()->first(),
            ]);
        }
        $UserEntity = (new WalletUserApiService())
            ->setRequest($requester->toArray())
            ->getWalletUserByTokenAndWalletId();

        if (is_null($UserEntity)) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => "系統錯誤",
            ]);
        }
        if ($UserEntity->is_admin == 1) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => "管理者不得使用此方式登入",
            ]);
        }

        # set cache
        $this->setMemberTokenCache($UserEntity);

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => null,
            'data'    => [
                'id'           => Arr::get($UserEntity, 'id'),
                'name'         => Arr::get($UserEntity, 'name'),
                'wallet_id'    => Arr::get($UserEntity, 'wallet_id'),
                'member_token' => Arr::get($UserEntity, 'token'),
                'wallet'       => [
                    'id'   => Arr::get($Wallet, 'id'),
                    'code' => Arr::get($Wallet, 'code'),
                ],
            ],
        ]);
    }
}
