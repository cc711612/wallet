<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:39
 */

namespace App\Http\Controllers\Apis\Wallets;

use App\Http\Controllers\ApiController;
use App\Http\Requesters\Apis\Wallets\Details\WalletDetailCheckoutRequest;
use App\Http\Requesters\Apis\Wallets\Details\WalletDetailDestroyRequest;
use App\Http\Requesters\Apis\Wallets\Details\WalletDetailIndexRequest;
use App\Http\Requesters\Apis\Wallets\Details\WalletDetailShowRequest;
use App\Http\Requesters\Apis\Wallets\Details\WalletDetailStoreRequest;
use App\Http\Requesters\Apis\Wallets\Details\WalletDetailUncheckoutRequest;
use App\Http\Requesters\Apis\Wallets\Details\WalletDetailUpdateRequest;
use App\Http\Resources\WalletDetailResource;
use App\Http\Validators\Apis\Wallets\Details\WalletDetailCheckoutValidator;
use App\Http\Validators\Apis\Wallets\Details\WalletDetailDestroyValidator;
use App\Http\Validators\Apis\Wallets\Details\WalletDetailIndexValidator;
use App\Http\Validators\Apis\Wallets\Details\WalletDetailStoreValidator;
use App\Http\Validators\Apis\Wallets\Details\WalletDetailUncheckoutValidator;
use App\Http\Validators\Apis\Wallets\Details\WalletDetailUpdateValidator;
use App\Models\SymbolOperationTypes\Contracts\Constants\SymbolOperationTypes;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Models\Wallets\Databases\Services\WalletDetailApiService;
use App\Models\Wallets\Databases\Services\WalletUserApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WalletDetailController extends ApiController
{
    /**
     * @var \App\Models\Wallets\Databases\Services\WalletApiService
     */
    private $walletApiService;
    /**
     * @var \App\Models\Wallets\Databases\Services\WalletDetailApiService
     */
    private $walletDetailApiService;
    /**
     * @var \App\Models\Wallets\Databases\Services\WalletUserApiService
     */
    private $walletUserApiService;

    /**
     * @param  \App\Models\Wallets\Databases\Services\WalletApiService  $walletApiService
     * @param  \App\Models\Wallets\Databases\Services\WalletDetailApiService  $walletDetailApiService
     * @param  \App\Models\Wallets\Databases\Services\WalletUserApiService  $walletUserApiService
     */
    public function __construct(
        WalletApiService $walletApiService,
        WalletDetailApiService $walletDetailApiService,
        WalletUserApiService $walletUserApiService
    ) {
        $this->walletApiService = $walletApiService;
        $this->walletDetailApiService = $walletDetailApiService;
        $this->walletUserApiService = $walletUserApiService;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:54
     */
    public function index(Request $request)
    {
        $requester = (new WalletDetailIndexRequest($request));

        $validate = (new WalletDetailIndexValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }

        $wallet = $this->walletApiService
            ->setRequest($requester->toArray())
            ->getWalletWithDetail();

        if (is_null($wallet)) {
            return $this->response()->fail("系統錯誤，請重新整理");
        }

        return $this->response()->success(
            (new WalletDetailResource($wallet))
                ->index()
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:54
     */
    public function store(Request $request)
    {
        $requester = (new WalletDetailStoreRequest($request));

        $validate = (new WalletDetailStoreValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }

        if (
            Arr::get($requester, 'wallet_details.type') == WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE &&
            Arr::get(
                $requester,
                'wallet_details.symbol_operation_type_id'
            ) == SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT
        ) {
            # 公費 & 減項 需檢查公費額度
            $walletBalance = $this->walletDetailApiService
                ->getWalletBalance(Arr::get($requester, 'wallets.id'));
            if ($walletBalance - Arr::get($requester, 'wallet_details.value') < 0) {
                return $this->response()->errorBadRequest("公費結算金額不得為負數");
            }
        }

        if (Arr::get($requester, 'wallet_details.type') != WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE) {
            # 驗證users
            $validateWalletUsers = $this->walletUserApiService
                ->setRequest($requester->toArray())
                ->validateWalletUsers();

            if ($validateWalletUsers === false) {
                return $this->response()->errorBadRequest("分攤成員有誤");
            }
        }

        try {
            $this->walletApiService
                ->setRequest($requester->toArray())
                ->createWalletDetail();
        } catch (\Exception $exception) {
            return $this->response()->fail(json_encode($exception));
        }

        return $this->response()->success();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:54
     */
    public function update(Request $request)
    {
        $requester = (new WalletDetailUpdateRequest($request));
        $validate = (new WalletDetailUpdateValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }
        if (
            Arr::get($requester, 'wallet_details.type') == WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE &&
            Arr::get(
                $requester,
                'wallet_details.symbol_operation_type_id'
            ) == SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT
        ) {
            # 公費 & 減項 需檢查公費額度
            $details = $this->walletDetailApiService
                ->getPublicDetailByWalletId(Arr::get($requester, 'wallets.id'));
            $beforeDetailValue = 0;
            # 更新前為公帳細項
            $updateDetail = $details->keyBy('id')->get(Arr::get($requester, 'wallet_details.id'));
            if (is_null($updateDetail) === false) {
                $beforeDetailValue = $updateDetail->value;
                if ($updateDetail->symbol_operation_type_id == SymbolOperationTypes::SYMBOL_OPERATION_TYPE_INCREMENT) {
                    $beforeDetailValue = 0 - $updateDetail->value;
                }
            }
            $detailGroupBySymbol = $details->groupBy('symbol_operation_type_id');
            $total = $detailGroupBySymbol->get(
                SymbolOperationTypes::SYMBOL_OPERATION_TYPE_INCREMENT,
                collect([])
            )->sum('value') - $detailGroupBySymbol->get(
                SymbolOperationTypes::SYMBOL_OPERATION_TYPE_DECREMENT,
                collect([])
            )->sum('value');
            # 檢查公帳負數問題
            if ($total + $beforeDetailValue - Arr::get($requester, 'wallet_details.value') < 0) {
                return $this->response()->errorBadRequest("公費結算金額不得為負數");
            }
        }
        if (Arr::get($requester, 'wallet_details.type') != WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE) {
            # 驗證users
            $validateWalletUsers = $this->walletUserApiService
                ->setRequest($requester->toArray())
                ->validateWalletUsers();
            if ($validateWalletUsers === false) {
                return $this->response()->errorBadRequest("分攤成員有誤");
            }
        }

        try {
            $this->walletDetailApiService
                ->setRequest($requester->toArray())
                ->updateWalletDetail();
        } catch (\Exception $exception) {
            return $this->response()->fail(json_encode($exception));
        }
        return $this->response()->success();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:54
     */
    public function show(Request $request)
    {
        $requester = (new WalletDetailShowRequest($request));

        $detail = $this->walletDetailApiService
            ->setRequest($requester->toArray())
            ->findDetail();

        # 認證
        if (is_null($detail) === true) {
            return $this->response()->errorBadRequest("參數有誤");
        }
        return $this->response()->success(
            (new WalletDetailResource($detail))
                ->show($requester->toArray())
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:54
     */
    public function destroy(Request $request)
    {
        $requester = (new WalletDetailDestroyRequest($request));

        $validate = (new WalletDetailDestroyValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }
        $detail = $this->walletDetailApiService
            ->find(Arr::get($requester, 'wallet_details.id'));

        if (is_null($detail) === true) {
            return $this->response()->errorBadRequest("參數有誤");
        }
        if ($detail->created_by != Arr::get($requester, 'wallet_users.id') && Arr::get(
            $requester,
            'wallet_user.is_admin'
        ) != 1) {
            return $this->response()->errorUnauthorized("非admin");
        }
        try {
            # 刪除
            $detail->update(Arr::get($requester, 'wallet_details'));
        } catch (\Exception $exception) {
            return $this->response()->fail(json_encode($exception));
        }
        return $this->response()->success();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:56
     */
    public function checkout(Request $request)
    {
        $requester = (new WalletDetailCheckoutRequest($request));

        $validate = (new WalletDetailCheckoutValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }
        try {

            $this->walletDetailApiService
                ->setRequest($requester->toArray())
                ->checkoutWalletDetails();
        } catch (\Exception $exception) {
            return $this->response()->fail(json_encode($exception));
        }

        return $this->response()->success();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy|void
     * @Author: Roy
     * @DateTime: 2022/7/31 下午 11:56
     */
    public function unCheckout(Request $request)
    {
        $requester = (new WalletDetailUncheckoutRequest($request));

        $validate = (new WalletDetailUncheckoutValidator($requester))->validate();
        if ($validate->fails() === true) {
            return $this->response()->errorBadRequest($validate->errors()->first());
        }

        try {

            $this->walletDetailApiService
                ->setRequest($requester->toArray())
                ->unCheckoutWalletDetails();
        } catch (\Exception $exception) {
            return $this->response()->fail(json_encode($exception));
        }

        return $this->response()->success();
    }
}
