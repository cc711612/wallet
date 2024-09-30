<?php

/**
 * @Author: Roy
 * @DateTime: 2023/7/25 下午 11:04
 */

namespace App\Http\Controllers\Apis\Users;

use App\Http\Controllers\ApiController;
use App\Http\Requests\UserThirdPartyRequest;
use App\Http\Resources\SocialResource;

class UserController extends ApiController
{
    public function socials(UserThirdPartyRequest $request)
    {
        $socials = $request->user->socials()->get();

        return $this->response()->success(
            SocialResource::collection($socials)
        );
    }
}
