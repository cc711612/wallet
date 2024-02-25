<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/19 下午 02:53
 */

namespace App\Http\Controllers\Mains\Auth;

use App\Models\Socials\Databases\Services\SocialService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;


/**
 * Class LoginController
 *
 * @package App\Http\Controllers\Mains\Auth
 * @Author: Roy
 * @DateTime: 2022/6/25 下午 08:32
 */
class LoginController extends Controller
{

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @Author: Roy
     * @DateTime: 2022/6/20 下午 03:16
     */
    public function login(Request $request)
    {
        return view('mains.auth.login');
    }

    public function lineLogin(Request $request)
    {
        return Socialite::driver('line')->redirect();
    }

    public function lineReturn(Request $request)
    {
        $token = Str::random(10);
        $prefix = 'auth.thirdParty.line.' . $token;
        $userInfo = Socialite::driver('line')->user();
        $userInfo = json_decode(json_encode($userInfo), 1);

        $socialEntity = app(SocialService::class)->registerLine($userInfo);
        Cache::put($prefix, $socialEntity, 300);
        $queries = [
            'token' => $token,
            'provider' => 'line'
        ];
        $url = config('services.easysplit.domain') . 'auth/thirdParty/return?' . http_build_query($queries);
        return redirect($url);
    }
}
