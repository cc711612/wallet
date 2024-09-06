<?php
/**
 * @Author: Roy
 * @DateTime: 2022/6/19 下午 02:53
 */

namespace App\Http\Controllers\Apis\Wallets\Auth;

use App\Http\Controllers\ApiController;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Http\Requesters\Apis\Wallets\Auth\RegisterRequest;
use App\Http\Requests\RegisterBatchRequest;
use App\Http\Validators\Apis\Wallets\Auth\RegisterValidator;
use function response;
use App\Models\Wallets\Databases\Services\WalletUserApiService;
use App\Traits\Wallets\Auth\WalletUserAuthLoginTrait;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Jobs\WalletUserRegister;


/**
 * Class WalletRegisterController
 *
 * @package App\Http\Controllers\Apis\Wallets
 * @Author: Roy
 * @DateTime: 2022/6/21 下午 12:05
 */
class WalletRegisterController extends ApiController
{
    use WalletUserAuthLoginTrait;

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/6/21 下午 03:25
     */
    public function register(Request $request)
    {
        $requester = (new RegisterRequest($request));

        $wallet = (new WalletApiService())
            ->setRequest($requester->toArray())
            ->getWalletByCode();

        $requester->__set('wallets.id', is_null($wallet) ? null : $wallet->id);

        $validate = (new RegisterValidator($requester))->validate();
        if ($validate->fails() === true) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => $validate->errors()->first(),
            ]);
        }

        $userEntity = (new WalletApiService())
            ->setRequest($requester->toArray())
            ->createWalletUserById();

        if (is_null($userEntity)) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => "系統錯誤",
            ]);
        }
        WalletUserRegister::dispatch(
            [
                'wallet_user,' => $userEntity,
                'wallet'       => $wallet,
            ]
        );
        # set cache
        $this->setMemberTokenCache($userEntity);

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => null,
            'data'    => [
                'id'           => Arr::get($userEntity, 'id'),
                'name'         => Arr::get($userEntity, 'name'),
                'member_token' => Arr::get($userEntity, 'token'),
                'wallet'       => [
                    'id'   => Arr::get($wallet, 'id'),
                    'code' => Arr::get($wallet, 'code'),
                ],
            ],
        ]);
    }

    public function registerBatch(RegisterBatchRequest $request)
    {
        /**
         * @var WalletApiService $walletApiService
         */
        $walletApiService = app(WalletApiService::class);
        try {
            $walletApiService->batchInsertWalletUserByWalletId(
                $request->wallet_id,
                $request->name
            );
        } catch (\Exception $e) {
            return $this->response()->errorBadRequest($e->getMessage());
        }
        return $this->response()->success();
    }
}
