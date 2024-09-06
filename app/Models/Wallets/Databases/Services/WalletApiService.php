<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/20 下午 03:40
 */

namespace App\Models\Wallets\Databases\Services;

use App\Concerns\Databases\Service;
use App\Jobs\WalletUserRegister;
use App\Models\ExchangeRates\Databases\Services\ExchangeRateService;
use Illuminate\Database\Eloquent\Model;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Users\Databases\Entities\UserEntity;
use App\Models\Wallets\Databases\Entities\WalletDetailEntity;
use App\Models\Wallets\Databases\Entities\WalletUserEntity;
use Illuminate\Support\Facades\DB;
use App\Traits\Wallets\Auth\WalletUserAuthCacheTrait;
use App\Models\Wallets\Contracts\Constants\WalletDetailTypes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use App\Traits\Caches\CacheTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Str;

class WalletApiService extends Service
{
    use WalletUserAuthCacheTrait, CacheTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @Author: Roy
     * @DateTime: 2022/7/10 下午 08:05
     */
    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(WalletEntity::class) === false) {
            app()->singleton(WalletEntity::class);
        }

        return app(WalletEntity::class);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 12:30
     */
    public function paginate(): LengthAwarePaginator
    {
        $pageCount = $this->getPageCount();

        # 一頁幾個
        if (is_null($this->getRequestByKey('per_page')) === false) {
            $pageCount = $this->getRequestByKey('per_page');
        }
        $walletIds = [];
        if ($this->getRequestByKey('wallets.is_guest')) {
            $walletUsers = app(WalletUserApiService::class)
                ->getWalletUserByUserId($this->getRequestByKey('users.id'));

            $walletIds = $walletUsers->where('is_admin', 0)->pluck('wallet_id')->toArray();
        }
        $result = $this->getEntity()
            ->with([
                UserEntity::Table => function ($query) {
                    $query->select(['id', 'name']);
                },
            ])
            ->when($this->getRequestByKey('wallets.is_guest'), function ($query) use ($walletIds) {
                return $query->whereIn('id', $walletIds);
            })
            ->when(!$this->getRequestByKey('wallets.is_guest'), function ($query) {
                return $query->where('user_id', $this->getRequestByKey('users.id'));
            })
            ->when(is_numeric($this->getRequestByKey('wallets.status')), function ($query) {
                return $query->where('status', $this->getRequestByKey('wallets.status'));
            })
            ->select(['id', 'user_id', 'title', 'code', 'unit', 'properties', 'status', 'updated_at', 'created_at']);

        return $result
            ->orderByDesc('updated_at')
            ->paginate($pageCount);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 12:32
     */
    public function getWalletWithDetail()
    {
        if (is_null($this->getRequestByKey('wallets.id'))) {
            return null;
        }

        $result = $this->getEntity()
            ->with([
                WalletDetailEntity::Table => function ($queryDetail) {
                    return $queryDetail
                        ->with([
                            WalletUserEntity::Table
                        ])
                        ->where(function ($query) {
                            $query
                                ->when($this->getRequestByKey('wallet_details.is_personal'), function ($subQuery) {
                                    $subQuery
                                        ->where('created_by', $this->getRequestByKey('wallet_users.id'));
                                })
                                ->when(!is_null($this->getRequestByKey('wallet_details.is_personal')), function ($subQuery) {
                                    $subQuery
                                        ->where('is_personal', $this->getRequestByKey('wallet_details.is_personal'));
                                });
                        })
                        ->orderByDesc('date')
                        ->orderByDesc('id');
                },
                WalletUserEntity::Table => function ($query) {
                    $query->select(['id', 'wallet_id', 'user_id', 'name', 'is_admin','created_at', 'updated_at']);
                }
            ])
            ->find($this->getRequestByKey('wallets.id'));

        $createTimes = $result->wallet_details
            ->pluck('created_at')
            ->map(function ($item) {
                return $item->format('Y-m-d');
            })
            ->uniqueStrict()
            ->values();

        $exchangeRateService = app(ExchangeRateService::class);
        $exchangeRates = $exchangeRateService->getExchangeRateByCurrencyAndDate($createTimes);
        $result->wallet_details = $result->wallet_details->map(function ($walletDetail) use ($exchangeRates) {
            $walletDetail->exchange_rates = $exchangeRates
                ->get($walletDetail->created_at->format('Y-m-d'), collect([]))
                ->values();
            return $walletDetail;
        });

        return $result;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 01:11
     */
    public function getWalletWithUserByCode()
    {
        $cacheKey = sprintf($this->getCacheKeyFormat(), $this->getRequestByKey('wallets.code'));
        # Cache

        if (Cache::has($cacheKey) === true) {
            return Cache::get($cacheKey);
        }
        $result = $this->getEntity()
            ->with([
                WalletUserEntity::Table,
            ])
            ->where('code', $this->getRequestByKey('wallets.code'))
            ->first();

        Cache::add($cacheKey, $result, 3600);
        return $result;
    }

    public function forgetWalletUsersCache($walletCode = null)
    {
        $cacheKey = sprintf($this->getCacheKeyFormat(), $walletCode);
        # cache forget
        if (Cache::has($cacheKey) === true) {
            return Cache::forget($cacheKey);
        }

        return true;
    }

    /**
     * @return mixed
     * @throws \Throwable
     * @Author: Roy
     * @DateTime: 2022/7/10 下午 09:42
     */
    public function createWalletWithUser()
    {
        return DB::transaction(function () {
            $entity = $this->getEntity()
                ->create($this->getRequestByKey('wallets'));
            $entity->wallet_users()->create($this->getRequestByKey('wallet_users'));
            $this->updateWalletUserCache($this->getRequestByKey('wallets.user_id'));
            return $entity;
        });
    }

    /**
     * @return mixed
     * @throws \Throwable
     * @Author: Roy
     * @DateTime: 2022/7/10 下午 09:42
     */
    public function createWalletUserById()
    {
        return DB::transaction(function () {
            $entity = $this->getEntity()
                ->find($this->getRequestByKey('wallets.id'));

            if (is_null($entity)) {
                return null;
            }
            $this->forgetCache(Arr::get($entity, 'code'));
            return $entity->wallet_users()->create($this->getRequestByKey('wallet_users'));
        });
    }

    public function batchInsertWalletUserByWalletId(int $walletId, array $names)
    {
        return DB::transaction(function () use ($walletId, $names) {
            $inserts = [];
            foreach ($names as $name) {
                $inserts[] = [
                    'wallet_id' => $walletId,
                    'name' => $name,
                    'token' => Str::random(12),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            WalletUserEntity::insert($inserts);
            $entity = $this->getEntity()
                ->find($walletId);
            $this->forgetCache(Arr::get($entity, 'code'));
            WalletUserRegister::dispatch(
                [
                    'wallet'       => $entity,
                ]
            );
        });
    }

    /**
     * @return null
     * @Author: Roy
     * @DateTime: 2022/7/10 下午 09:42
     */
    public function getWalletByCode()
    {
        if (is_null($this->getRequestByKey('wallets.code'))) {
            return null;
        }
        return $this->getEntity()
            ->where('status', 1)
            ->where('code', $this->getRequestByKey('wallets.code'))
            ->first();
    }

    /**
     * @param  array  $create
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @Author: Roy
     * @DateTime: 2022/6/22 上午 12:04
     */
    public function createWalletDetail()
    {
        if (is_null($this->getRequestByKey('wallets.id'))) {
            return null;
        }

        return DB::transaction(function () {
            $entity = $this->getEntity()
                ->find($this->getRequestByKey('wallets.id'));
            if (is_null($entity) === true) {
                return null;
            }
            $detailEntity = $entity->wallet_details()->create($this->getRequestByKey('wallet_details'));
            # 不等於公帳
            if ($this->getRequestByKey('wallet_details.type') != WalletDetailTypes::WALLET_DETAIL_TYPE_PUBLIC_EXPENSE) {
                $users = $this->getRequestByKey('wallet_detail_wallet_user');
                # 全選
                if ($this->getRequestByKey('wallet_details.select_all') == 1) {
                    $users = $entity->wallet_users()->get()->pluck('id')->toArray();
                }
                # 分帳人
                $detailEntity->wallet_users()->sync($users);
            }
            return $detailEntity;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @Author: Roy
     * @DateTime: 2022/7/5 上午 10:34
     */
    public function getWalletUsersAndDetails()
    {
        if (is_null($this->getRequestByKey('wallets.id'))) {
            return null;
        }
        return $this->getEntity()
            ->with([
                WalletDetailEntity::Table => function ($queryDetail) {
                    return $queryDetail->with([
                        WalletUserEntity::Table,
                    ]);
                },
                WalletUserEntity::Table,
            ])
            ->find($this->getRequestByKey('wallets.id'));
    }
}
