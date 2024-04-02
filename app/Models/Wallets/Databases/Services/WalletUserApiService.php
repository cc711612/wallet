<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/21 下午 02:42
 */

namespace App\Models\Wallets\Databases\Services;

use App\Concerns\Databases\Service;
use Illuminate\Database\Eloquent\Model;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use Illuminate\Support\Facades\DB;
use App\Traits\Caches\CacheTrait;
use App\Traits\Wallets\Auth\WalletUserAuthLoginTrait;
use Crypt;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WalletUserApiService extends Service
{
    use CacheTrait, WalletUserAuthLoginTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @Author: Roy
     * @DateTime: 2022/6/21 下午 02:43
     */
    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(WalletUserEntity::class) === false) {
            app()->singleton(WalletUserEntity::class);
        }

        return app(WalletUserEntity::class);
    }

    /**
     * @return null
     * @Author: Roy
     * @DateTime: 2022/7/4 下午 06:49
     */
    public function validateWalletUsers()
    {
        if (is_null($this->getRequestByKey('wallets.id'))) {
            return false;
        }

        return $this->getEntity()
            ->where('wallet_id', $this->getRequestByKey('wallets.id'))
            ->whereIn('id', $this->getRequestByKey('wallet_detail_wallet_user'))
            ->count() == count($this->getRequestByKey('wallet_detail_wallet_user'));
    }

    /**
     * @return WalletUserEntity|null
     * @Author: Roy
     * @DateTime: 2022/6/21 下午 05:11
     */
    public function getWalletUserByNameAndWalletId()
    {
        if (is_null($this->getRequestByKey('wallet_users.wallet_id')) || is_null($this->getRequestByKey('wallet_users.name'))) {
            return null;
        }
        return $this->getEntity()
            ->where('wallet_id', $this->getRequestByKey('wallet_users.wallet_id'))
            ->where('name', $this->getRequestByKey('wallet_users.name'))
            ->first();
    }

    /**
     * @return null
     * @Author: Roy
     * @DateTime: 2022/6/28 上午 05:38
     */
    public function getWalletUserByTokenAndWalletId()
    {
        if (is_null($this->getRequestByKey('wallet_users.wallet_id')) || is_null($this->getRequestByKey('wallet_users.token'))) {
            return null;
        }
        return $this->getEntity()
            ->where('wallet_id', $this->getRequestByKey('wallet_users.wallet_id'))
            ->where('token', $this->getRequestByKey('wallet_users.token'))
            ->first();
    }

    /**
     * @return WalletUserEntity|null
     * @Author: Roy
     * @DateTime: 2022/7/4 下午 06:23
     */
    public function getUserWithDetail()
    {
        return $this->getEntity()
            ->with([
                "created_wallet_details",
                "wallet_details",
            ])
            ->find($this->getRequestByKey('wallet_users.id'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @Author: Roy
     * @DateTime: 2022/7/4 下午 06:35
     */
    public function delete()
    {
        return DB::transaction(function () {
            $UserEntity = $this->getEntity()
                ->with([WalletEntity::Table])
                ->find($this->getRequestByKey('wallet_users.id'));

            if (is_null($UserEntity)) {
                return null;
            }

            $this->forgetCache(Arr::get($UserEntity, 'wallets.code'));
            return $UserEntity->update($this->getRequestByKey('wallet_users'));
        });
    }

    /**
     * @param $wallet_id
     *
     * @return mixed
     * @Author: Roy
     * @DateTime: 2022/7/4 下午 11:20
     */
    public function getWalletUserByWalletId($wallet_id)
    {
        return $this->getEntity()
            ->where('wallet_id', $wallet_id)
            ->get();
    }

    public function getWalletUserByWalletUserId($userId): Collection
    {
        $cacheKey = 'wallet_user_' . $userId;
        if (Cache::get($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $walletUsers = $this->getEntity()
            ->where('id', $userId)
            ->get();
        if ($walletUsers->isNotEmpty()) {
            Cache::put($cacheKey, $walletUsers, 3600);
        }
        return $walletUsers;
    }

    public function forgetCacheByWalletUser(WalletUserEntity $walletUserEntity)
    {
        if (Cache::has('wallet_user_' . $walletUserEntity->id)) {
            Log::channel('token')->info(sprintf("forgetCacheByWalletUser : %s ", json_encode([
                'cache_key' => 'wallet_user_' . $walletUserEntity->id,
                'token'     => $walletUserEntity->token,
            ])));
            Cache::forget('wallet_user_' . $walletUserEntity->id);
        }
        $this->cleanToken($walletUserEntity->token);
    }

    public function getWalletUserByUserId($userId): Collection
    {
        return $this->getEntity()
            ->select(['id', 'user_id', 'wallet_id', 'is_admin'])
            ->where('user_id', $userId)
            ->get();
    }

    public function walletUserBindByUserId($userId, $jwtToken)
    {
        $tokenPayload = JWT::decode($jwtToken, new Key(config('app.name'), 'HS256'));
        // toArray
        $tokenPayload = $tokenPayload ? json_decode(json_encode($tokenPayload), 1) : [];
        if (!empty($tokenPayload['wallet_user']['id'])) {
            $walletUserId = Crypt::decryptString($tokenPayload['wallet_user']['id']);
            $walletUser = $this->getWalletUserByWalletUserId($walletUserId);
            if ($walletUser->isNotEmpty()) {
                $walletUser = $walletUser->first();
                $walletId = $walletUser->wallet_id;
                // 檢查是否重複帳本成員以及是否被綁定
                if (
                    !$this->isExistWalletUserByWalletIdAndUserId($walletId, $userId) &&
                    is_null($walletUser->user_id)
                ) {
                    $walletUser->user_id = $userId;
                    $walletUser->save();
                    return [
                        'status' => true,
                        'message' => '綁定成功'
                    ];
                }
                return [
                    'status' => false,
                    'message' => '已被綁定或是有重複的帳本使用者'
                ];
            }
        }
        return [
            'status' => false,
            'message' => '系統錯誤查詢不到綁定資訊'
        ];
    }

    public function walletUserBindByUserIdAndWalletId($userId, $walletId, $walletUserId)
    {
        if ($this->isExistWalletUserByWalletIdAndUserId($walletId, $userId)) {
            return [
                'status' => false,
                'message' => '已綁定過此帳本'
            ];
        }

        $walletUser = $this->getEntity()
            ->where('id', $walletUserId)
            ->where('wallet_id', $walletId)
            ->where('user_id', null)
            ->first();

        if ($walletUser) {
            $walletUser->user_id = $userId;
            $walletUser->save();
            return [
                'status' => true,
                'message' => '綁定成功'
            ];
        }

        return [
            'status' => false,
            'message' => '已被綁定或是有重複的帳本使用者'
        ];
    }

    public function isExistWalletUserByWalletIdAndUserId($walletId, $userId)
    {
        return $this->getEntity()
            ->where('wallet_id', $walletId)
            ->where('user_id', $userId)
            ->exists();
    }
}
