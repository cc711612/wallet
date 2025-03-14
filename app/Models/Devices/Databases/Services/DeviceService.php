<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/21 上午 11:25
 */

namespace App\Models\Devices\Databases\Services;

use App\Concerns\Databases\Service;
use App\Models\Devices\Databases\Entities\DeviceEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DeviceService extends Service
{
    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @Author: Roy
     */
    protected function getEntity(): Model
    {
        if (app()->has(DeviceEntity::class) === false) {
            app()->singleton(DeviceEntity::class);
        }

        return app(DeviceEntity::class);
    }

    public function index($userId, $walletUserId): Collection
    {
        return $this->getEntity()
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($walletUserId, function ($query) use ($walletUserId) {
                $query->where('wallet_user_id', $walletUserId);
            })
            ->where('expired_at', '>', now())
            ->get();
    }

    /**
     * update or create
     * @param array $params
     * @return void
     */
    public function updateOrCreate($params)
    {
        $deviceEntity = $this->getEntity();
        return $deviceEntity->updateOrCreate(
            [
                'user_id' => data_get($params, 'user_id'),
                'wallet_user_id' => data_get($params, 'wallet_user_id'),
                'platform' => data_get($params, 'platform'),
                'fcm_token' => data_get($params, 'fcm_token'),
            ],
            [
                'user_id' => data_get($params, 'user_id'),
                'wallet_user_id' => data_get($params, 'wallet_user_id'),
                'platform' => data_get($params, 'platform'),
                'fcm_token' => data_get($params, 'fcm_token'),
                'device_name' => data_get($params, 'device_name'),
                'device_type' => data_get($params, 'device_type'),
                'expired_at' => data_get($params, 'expired_at'),
            ]
        );
    }

    public function delete(int $id)
    {
        return $this->getEntity()->where('id', $id)->delete();
    }

    public function deleteById($id, $userId, $walletUserId)
    {
        return $this->getEntity()
            ->where('id', $id)
            ->where(function ($query) use ($userId, $walletUserId) {
                $query->where('user_id', $userId)
                    ->orWhere('wallet_user_id', $walletUserId);
            })
            ->delete();
    }

    public function getActiveDeviceByUserId($userId, $walletUserId)
    {
        return $this->getEntity()
            ->where('expired_at', '>', now())
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($walletUserId && !$userId, function ($query) use ($walletUserId) {
                $query->where('wallet_user_id', $walletUserId);
            })
            ->get();
    }
}
