<?php

namespace App\Http\Requesters\Apis\Auth;

use App\Concerns\Databases\Request;
use Illuminate\Support\Arr;

class LoginRequest extends Request
{
    /**
     * @return null[]
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function schema(): array
    {
        return [
            'account'        => null,
            'users.account'  => null,
            'password'       => null,
            'users.password' => null,
            'users.ip' => null,
            'users.agent' => null,
            'jwt_token' => null,
            'type' => null,
        ];
    }

    /**
     * @param $row
     *
     * @return array
     * @Author  : Roy
     * @DateTime: 2020/12/15 下午 03:02
     */
    protected function map($row): array
    {
        return [
            'account'        => Arr::get($row, 'account'),
            'users.account'  => Arr::get($row, 'account'),
            'password'       => Arr::get($row, 'password'),
            'users.password' => Arr::get($row, 'password'),
            'type' => Arr::get($row, 'type'),
            'jwt_token' => $row->bearerToken(),
            'users.ip' => $row->ip(),
            'users.agent' => $row->header('User-Agent'),
        ];
    }
}
