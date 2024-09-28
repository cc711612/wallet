<?php

/**
 * @Author: Roy
 * @DateTime: 2023/7/25 下午 11:04
 */

namespace App\Http\Controllers\Apis\Socials;

use App\Http\Controllers\ApiController;
use App\Http\Requests\SocialBindRequest;
use App\Http\Requests\SocialCheckBindRequest;
use App\Http\Resources\AuthResource;
use App\Models\Socials\Databases\Services\SocialService;
use App\Traits\AuthLoginTrait;
use Auth;
use Cache;
use Illuminate\Support\Str;

class SocialController extends ApiController
{
    use AuthLoginTrait;

    public function checkBind(SocialCheckBindRequest $request)
    {
        $socialType = $request->input('socialType');
        $socialTypeValue = $request->input('socialTypeValue');
        /**
         * @var SocialService $socialService
         */
        $socialService = app(SocialService::class);
        $social = $socialService->getBySocialTypeAndSocialTypeValue($socialType, $socialTypeValue);
        if (is_null($social) || is_null($social->users->first())) {
            $social = $socialService->updateOrCreate($request->all());
            $token = Str::random(12);
            $cacheKey = 'registerByToken_' . $token;
            Cache::put($cacheKey, $social, 300);
            return $this->response()->success([
                'action' => 'not bind',
                'token' => $token,
            ]);
        } else {
            $user = $social->users->first();
            $user->agent = request()->header('User-Agent');
            $user->ip = request()->ip();
            $user->save();
            Auth::login($user);

            return $this->response()->success(
                array_merge(
                    (new AuthResource(Auth::user()))
                        ->login(), [
                        'action' => 'bind',
                    ])
            );
        }
    }

    public function bind(SocialBindRequest $request)
    {
        $token = $request->input('token');
        $cacheKey = 'registerByToken_' . $token;
        $social = Cache::pull($cacheKey);
        if (is_null($social)) {
            return $this->response()->errorBadRequest('token is invalid');
        }
        $social->users()->attach($request->user->id);
        return $this->response()->success();
    }
}
