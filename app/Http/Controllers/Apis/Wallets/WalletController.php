<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:39
 */

namespace App\Http\Controllers\Apis\Wallets;

use Illuminate\Http\Request;
use App\Http\Requesters\Apis\Wallets\WalletIndexRequest;
use Illuminate\Support\Arr;
use App\Http\Requesters\Apis\Wallets\WalletStoreRequest;
use App\Http\Validators\Apis\Wallets\WalletStoreValidator;
use App\Http\Requesters\Apis\Wallets\WalletUpdateRequest;
use App\Http\Validators\Apis\Wallets\WalletUpdateValidator;
use App\Http\Requesters\Apis\Wallets\WalletCalculationRequest;
use App\Http\Validators\Apis\Wallets\WalletCalculationValidator;
use App\Http\Controllers\ApiController;
use App\Http\Requesters\Apis\Wallets\WalletBindRequest;
use App\Http\Resources\WalletResource;
use App\Http\Validators\Apis\Wallets\Auth\LoginValidator;
use App\Models\Wallets\Databases\Services\WalletApiRelayService;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Models\Wallets\Databases\Services\WalletUserApiService;

class WalletController extends ApiController
{
    public bool $relayEnable;

    public function __construct(
        private WalletApiService $walletApiService,
        private WalletApiRelayService $walletApiRelayService,
        private WalletUserApiService $walletUserApiService
    ) {
        $this->relayEnable = config('services.walletApi.relayEnable');
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 02:19
     */
    public function index(Request $request)
    {
        $requester = (new WalletIndexRequest($request));
        if ($this->relayEnable) {
            $response = $this->walletApiRelayService
                ->getWallets(
                    data_get($requester->toArray(), 'users.id')
                );
            return $this->response()->success($response['data'], $response['message'], $response['code']);
        }
        $wallets = $this->walletApiService
            ->setRequest(array_merge($requester->toArray(), ['page_count' => $requester->page_count]))
            ->paginate();

        return $this->response()->success(
            (new WalletResource($wallets))
                ->index()
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 02:19
     */
    public function store(Request $request)
    {
        $requester = new WalletStoreRequest($request);
        $validate = (new WalletStoreValidator($requester))->validate();
        if ($this->validationFails($validate)) return $this->validationErrorResponse($validate);
        try {
            $wallet = $this->walletApiService
                ->setRequest($requester->toArray())
                ->createWalletWithUser();
            return $this->response()->success((new WalletResource($wallet))->store());
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/6/20 下午 10:29
     */
    public function update(Request $request)
    {
        $requester = new WalletUpdateRequest($request);
        $validate = (new WalletUpdateValidator($requester))->validate();
        if ($this->validationFails($validate)) return $this->validationErrorResponse($validate);
        try {
            // 獲取原始請求數據，只更新有提供的欄位
            $originalData = $request->all();
            $walletData = Arr::get($requester, 'wallets');
            
            // 過濾掉未在原始請求中提供的欄位
            $filteredData = array_filter($walletData, function($value, $key) use ($originalData) {
                // 特殊處理 mode 欄位，如果原始請求中沒有提供，則不更新
                if ($key === 'mode' && !isset($originalData['mode'])) {
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_BOTH);
            
            $this->walletApiService->update(
                Arr::get($requester, 'wallets.id'),
                $filteredData
            );
            return $this->response()->success();
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/7/5 上午 10:15
     */
    public function calculation(Request $request)
    {
        $requester = new WalletCalculationRequest($request);
        $validate = (new WalletCalculationValidator($requester))->validate();
        if ($this->validationFails($validate)) return $this->validationErrorResponse($validate);
        try {
            $wallet = $this->walletApiService
                ->setRequest($requester->toArray())
                ->getWalletUsersAndDetails();
            return $this->response()->success((new WalletResource($wallet))->calculation());
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        }
    }

    public function bind(Request $request)
    {
        $requester = new WalletBindRequest($request);
        $wallet = $this->walletApiService
            ->setRequest($requester->toArray())
            ->getWalletByCode();
        if (is_null($wallet)) {
            return $this->response()->errorBadRequest("此帳簿不存在");
        }
        $requester->__set('wallets.id', $wallet->id);
        $requester->__set('wallet_users.wallet_id', $wallet->id);
        $validate = (new LoginValidator($requester))->validate();
        if ($this->validationFails($validate)) return $this->validationErrorResponse($validate);
        try {
            $userEntity = $this->walletUserApiService
                ->setRequest($requester->toArray())
                ->getWalletUserByNameAndWalletId();
            if (is_null($userEntity)) {
                return $this->response()->errorBadRequest("系統錯誤");
            }
            $bind = $this->walletUserApiService
                ->walletUserBindByUserIdAndWalletId(
                    $requester->users['id'],
                    $requester->wallets['id'],
                    $userEntity->id
                );
            if ($bind['status'] === false) {
                return $this->response()->errorBadRequest($bind['message']);
            }
            return $this->response()->success(null, '綁定成功');
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        }
    }

    /**
     * 刪除錢包
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $wallet = $this->walletApiService->findOrFail($id);
            // 檢查用戶是否有權限刪除此錢包
            if (!$this->walletApiService->canUserDeleteWallet($request->user()->id, $wallet->id)) {
                return $this->response()->errorForbidden('您沒有權限刪除此錢包');
            }
            $this->walletApiService->delete($wallet);
            return $this->response()->success(null, '錢包已成功刪除');
        } catch (\Throwable $e) {
            return $this->response()->errorForbidden('刪除錢包時發生錯誤：' . $e->getMessage());
        }
    }
}
