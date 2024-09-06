<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:39
 */

namespace App\Http\Controllers\Apis\Wallets;

use Illuminate\Http\Request;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Http\Requesters\Apis\Wallets\Users\WalletUserIndexRequest;
use App\Http\Validators\Apis\Wallets\Users\WalletUserIndexValidator;
use App\Http\Requesters\Apis\Wallets\Users\WalletUserDestroyRequest;
use App\Http\Validators\Apis\Wallets\Users\WalletUserDestroyValidator;
use App\Models\Wallets\Databases\Services\WalletUserApiService;
use App\Http\Resources\WalletUserResource;
use App\Http\Controllers\ApiController;
use App\Http\Requesters\Apis\Wallets\Users\WalletUserUpdateRequest;
use App\Http\Validators\Apis\Wallets\Users\WalletUserUpdateValidator;
use Illuminate\Support\Arr;

class WalletUserController extends ApiController
{
    /**
     * @var \App\Models\Wallets\Databases\Services\WalletApiService
     */
    private $walletApiService;
    /**
     * @var \App\Models\Wallets\Databases\Services\WalletUserApiService
     */
    private $walletUserApiService;


    /**
     * @param  \App\Models\Wallets\Databases\Services\WalletApiService  $walletApiService
     */
    public function __construct(
        WalletApiService $walletApiService,
        WalletUserApiService $walletUserApiService
    ) {
        $this->walletApiService = $walletApiService;
        $this->walletUserApiService = $walletUserApiService;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 02:03
     */
    public function index(Request $request)
    {
        $requester = (new WalletUserIndexRequest($request));

        $validate = (new WalletUserIndexValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }

        $wallet = $this->walletApiService
            ->setRequest($requester->toArray())
            ->getWalletWithUserByCode();

        return $this->response()->success((new WalletUserResource($wallet))->index());
    }

    public function update(Request $request)
    {
        $walletUser  = $this->walletUserApiService
            ->find(Arr::get($request, 'wallet_users_id'));
        if ($walletUser) {
            $request->merge([
                'wallet_id' => $walletUser->wallet_id,
            ]);
        }
        $requester = (new WalletUserUpdateRequest($request));

        $validate = (new WalletUserUpdateValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }

        $this->walletUserApiService
            ->update(
                Arr::get($requester, 'wallet_users.id'),
                Arr::get($requester, 'wallet_users'),
            );

        return $this->response()->success();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 02:03
     */
    public function destroy(Request $request)
    {
        $requester = (new WalletUserDestroyRequest($request));

        $validate = (new WalletUserDestroyValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }
        $walletUsers = $this->walletUserApiService
            ->setRequest($requester->toArray())
            ->getUserWithDetail();

        # 驗證
        if (is_null($walletUsers) === false && $walletUsers->created_wallet_details->isEmpty() === false) {
            return $this->response()->errorBadRequest("成員已新增細項,無法刪除");
        }
        try {
            $this->walletUserApiService
                ->setRequest($requester->toArray())
                ->delete();
        } catch (\Exception $e) {
            return $this->response()->fail($e);
        }

        return $this->response()->success();
    }
}
