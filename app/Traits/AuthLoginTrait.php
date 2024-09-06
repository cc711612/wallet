<?php
/**
 * @Author: Roy
 * @DateTime: 2021/8/12 下午 09:04
 */

namespace App\Traits;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait AuthLoginTrait
{
    /**
     * @Author: Roy
     * @DateTime: 2021/8/12 下午 09:13
     */
    private function MemberTokenCache()
    {
        # 更新token
        $this->updateToken();
    }

    /**
     * @return $this
     * @Author: Roy
     * @DateTime: 2021/8/12 下午 09:13
     */
    private function updateToken()
    {
        try {
            # 檢查token
            if ($this->checkToken()) {
                # 清除cache
                $this->cleanToken();
            }
            $user = Auth::user();
            $token = Str::random(12);
            $user->token = $token;
            $cache = Cache::add(sprintf(config('cache_key.api.member_token'), $token), Auth::user(),
                config('app.login_timeout'));
            $cacheWalletUser = Cache::add(sprintf(config('cache_key.api.wallet_member_token'), $token),
                Auth::user()->wallet_users()->get()->keyBy('wallet_id'),
                config('app.login_timeout'));
            # Log
            if ($cache === true && $cacheWalletUser === true) {
                Log::channel('token')->info(sprintf("Login info : %s ", json_encode([
                    'userId'   => $user->id,
                    'cacheKey' => sprintf(config('cache_key.api.member_token'), $token),
                    'token'    => $token,
                    'endTime'  => Carbon::now()->addSeconds(config('app.login_timeout'))->toDateTimeString(),
                ])));
            };
            $user->wallet_users()->update([
                'token' => $token,
            ]);
            $user->save();
        } catch (\Throwable $exception) {
            Log::channel('error')->info(sprintf("Login errors : %s ", json_encode($exception, JSON_UNESCAPED_UNICODE)));
        }
        return $this;
    }

    /**
     * @param  string|null  $token
     *
     * @return bool
     * @Author: Roy
     * @DateTime: 2021/8/13 上午 10:44
     */
    private function checkToken(string $token = null)
    {

        if (is_null($token) === true) {
            $token = Arr::get(Auth::user(), 'token');
        }
        return Cache::has($this->getCacheKey($token)) || Cache::has($this->getWalletUserCacheKey($token));
    }

    /**
     * @return bool
     * @Author: Roy
     * @DateTime: 2021/8/13 上午 10:36
     */
    private function cleanToken()
    {
        Log::channel('token')->info(sprintf("Token Clean info : %s ", $this->getCacheKey()));
        return Cache::forget($this->getCacheKey()) && Cache::has($this->getWalletUserCacheKey());
    }

    /**
     * @param  string|null  $token
     *
     * @return string
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 01:49
     */
    private function getCacheKey(string $token = null)
    {

        if (is_null($token) === true) {
            $token = Arr::get(Auth::user(), 'token');
        }
        return sprintf(config('cache_key.api.member_token'), $token);
    }

    /**
     * @param  string|null  $token
     *
     * @return string
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 01:49
     */
    private function getWalletUserCacheKey(string $token = null)
    {
        if (is_null($token) === true) {
            $token = Arr::get(Auth::user(), 'token');
        }
        return sprintf(config('cache_key.api.wallet_member_token'), $token);
    }
}

