<?php

/**
 * @Author: Roy
 * @DateTime: 2025/3/21 上午 11:25
 */

namespace App\Models\Socials\Databases\Services;

use App\Concerns\Databases\Service;
use App\Jobs\LineWebhookJob;
use App\Models\Socials\Contracts\Constants\SocialType;
use App\Models\Socials\Databases\Entities\SocialEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LineService extends Service
{
    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 11:27
     */
    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(SocialEntity::class) === false) {
            app()->singleton(SocialEntity::class);
        }

        return app(SocialEntity::class);
    }

    public function webhook($data, $socialType = SocialType::SOCIAL_TYPE_LINE)
    {
        if ($socialType === SocialType::SOCIAL_TYPE_LINE) {
            LineWebhookJob::dispatch($data);
        }
        return true;
    }

    public function connectedWalletId($userId, $walletId)
    {
        $cacheKey = sprintf('line_connected_wallet_%s', $userId);
        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
        }
        Cache::put($cacheKey, $walletId, 60 * 60 * 24 * 30 * 12);

        return true;
    }

    public function getConnectedWalletId($userId)
    {
        $cacheKey = sprintf('line_connected_wallet_%s', $userId);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        return null;
    }
}
