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
use App\Http\Requests\WalletUsers\WalletUserUpdateRequest;

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
        if ($this->validationFails($validate)) return $this->validationErrorResponse($validate);

        $wallet = $this->walletApiService
            ->setRequest($requester->toArray())
            ->getWalletWithUserByCode();

        return $this->response()->success((new WalletUserResource($wallet))->index());
    }

    public function update(WalletUserUpdateRequest $request)
    {
        $data = $request->only([
            'wallet_user_id',
            'name',
            'notify_enable',
        ]);
        try {
            $this->walletUserApiService
                ->update(
                    $data['wallet_user_id'],
                    $data,
                );
            return $this->response()->success();
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        }
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
        if ($this->validationFails($validate)) return $this->validationErrorResponse($validate);
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
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        }

        return $this->response()->success();
    }
}
