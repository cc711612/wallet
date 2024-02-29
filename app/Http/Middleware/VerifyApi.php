<?php

namespace App\Http\Middleware;

use App\Models\Users\Databases\Services\UserApiService;
use Illuminate\Support\Facades\Cache;
use App\Traits\AuthLoginTrait;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Crypt;

class VerifyApi
{
    use AuthLoginTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     *
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = null)
    {
        // jwt
        if ($request->bearerToken()) {
            $tokenPayload = JWT::decode($request->bearerToken(), new Key(config('app.name'), 'HS256'));
            // toArray
            $tokenPayload = $tokenPayload ? json_decode(json_encode($tokenPayload), 1) : [];
            if (!empty($tokenPayload['user']['id'])) {
                $userId = Crypt::decryptString($tokenPayload['user']['id']);
                $user = app(UserApiService::class)->find($userId);
                if ($user) {
                    $request->merge([
                        'user' => $user,
                    ]);
                    return $next($request);
                }
            }
        }

        // token
        $member_token = $request->member_token;
        if ($member_token == null) {
            return response()->json([
                'status'  => false,
                'code'    => 400,
                'message' => '請帶入 member_token',
                'data'    => [],
            ]);
        }

        if ($this->checkToken($member_token) === false) {
            return response()->json($this->get_error_response($member_token));
        }
        # 取得快取資料
        $LoginCache = Cache::get($this->getCacheKey($member_token));
        if (is_null($LoginCache) === true) {
            return response()->json($this->get_error_response($member_token));
        }
        # 若有新增請記得至 ResponseApiServiceProvider 排除
        $request->merge([
            'user' => $LoginCache,
        ]);

        return $next($request);
    }

    private function get_error_response($token)
    {
        Log::channel('token')->info(sprintf("Verify token is empty info : %s ", $token));
        return [
            'status'  => false,
            'code'    => 401,
            'message' => "請重新登入",
            'data'    => [],
        ];
    }
}
