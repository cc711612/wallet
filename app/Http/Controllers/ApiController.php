<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Traits\ApiPaginateTrait;
use App\Traits\ResponseTrait;

class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use ApiPaginateTrait, ResponseTrait;

    /**
     * 驗證失敗處理
     * @param $validate
     * @return bool
     */
    protected function validationFails($validate): bool
    {
        return $validate->fails() === true;
    }

    /**
     * 驗證失敗回應
     * @param $validate
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse($validate)
    {
        return $this->response()->errorBadRequest($validate->errors()->first());
    }
}

