<?php

namespace App\Http\Middleware;

use App\Models\Users\Databases\Services\UserApiService;
use Illuminate\Support\Facades\Cache;
use App\Traits\AuthLoginTrait;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

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
            try {
                $tokenPayload = JWT::decode($request->bearerToken(), new Key(config('app.name'), 'HS256'));
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => false,
                    'code'    => 401,
                    'message' => '請重新登入',
                    'data'    => [],
                ], 401);
            }
            // toArray
            $tokenPayload = $tokenPayload ? json_decode(json_encode($tokenPayload), 1) : [];
            if (!empty($tokenPayload['user']['id'])) {
                $userId = Crypt::decryptString($tokenPayload['user']['id']);
                $user = app(UserApiService::class)->find($userId);
                if ($user) {
                    app(UserApiService::class)->update($userId, [
                        'agent' => $request->header('User-Agent'),
                        'ip' => $request->ip(),
                    ]);
                    $request->merge([
                        'user' => $user,
                    ]);
                    return $next($request);
                }
            }
        }

        // token
        $member_token = $request->member_token;

        // 如果 member_token 為空，返回錯誤響應
        if ($member_token == null && !$this->isBlockRoute($request)) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => '請帶入 member_token',
                'data'    => [],
            ], 401);
        }

        // 如果不是阻塞路由，進行進一步的驗證
        if (!$this->isBlockRoute($request)) {
            // 檢查 token 是否有效
            if ($this->checkToken($member_token) === false || is_null($LoginCache = Cache::get($this->getCacheKey($member_token)))) {
                // 驗證失敗，返回錯誤響應
                return response()->json($this->get_error_response($member_token), 401);
            }

            // 如果 token 驗證成功且用戶資料從快取中獲取成功
            // 將用戶資料合併到請求中，以便後續使用
            $request->merge(['user' => $LoginCache]);
        }

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

    /**
     * 檢查是否為排除的路由
     */
    private function isBlockRoute(Request $request)
    {
        $blockRoutes = [
            'api.auth.logout'
        ];
        return $request->routeIs($blockRoutes);
    }
}
