<?php

/**
 * @Author: Roy
 * @DateTime: 2022/9/10 下午 04:14
 */

namespace App\Http\Controllers\Apis\Logs;

use App\Http\Controllers\ApiController;
use App\Models\Users\Databases\Entities\UserEntity;
use App\Notifications\LineNotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Http;
class LineController extends ApiController
{

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Support\HigherOrderTapProxy
     * @Author: Roy
     * @DateTime: 2022/9/10 下午 04:16
     */
    public function store(Request $request)
    {
        Log::channel('bot')->info($request->toArray());
        return $this->response()->success();
    }

    /**
     * @param Request $request
     * @return JSON
     */
    public function notify(Request $request)
    {
        Log::channel('bot')->info($request->toArray());

        if ($request->get('userId') && $request->get('code')) {
            $token = $this->notifyToken($request->get('code'), $request->get('userId'));
            if ($token) {
                UserEntity::find($request->get('userId'))->update(
                    ['notify_token' => $token]
                );
            }
        }

        return $this->response()->success();
    }

    /**
     * 取得通知的Token
     *
     * @param string $code
     * @param int $userId
     * @return string|null
     */
    private function notifyToken($code, $userId)
    {
        $params = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('api.webhook.line.notify', ['userId' => $userId]),
            'client_id' => env('LINE_NOTIFY_CLIENT'),
            'client_secret' => env('LINE_NOTIFY_SECRET'),
            'code' => $code,
        ];
        $response = Http::asForm()->post('https://notify-bot.line.me/oauth/token', $params);

        // 检查响应状态
        if ($response->successful()) {
            return data_get(json_decode($response->body(), true), 'access_token');
        } else {
            return null;
        }
    }

    /**
     * 回傳綁定的URL
     *
     * @param Request $request
     * @return JSON
     */
    public function notifyBind(Request $request)
    {
        $bindUrl = 'https://notify-bot.line.me/oauth/authorize?response_type=code&scope=notify&response_mode=form_post&client_id={clientId}&redirect_uri={redirectUrl}&state={state}';
        $bindUrl = str_replace(
            ['{clientId}', '{redirectUrl}', '{state}'],
            [env('LINE_NOTIFY_CLIENT'), route('api.webhook.line.notify', ['userId' => $request->user->id]), Str::uuid()],
            $bindUrl
        );

        return $this->response()->success(['url' => $bindUrl]);
    }

    public function notifySendMessage(Request $request)
    {
        $user = $request->user;
        $contents = [
            '有一筆新的記帳資料',
            '帳本名稱：測試帳本',
            '記帳日期：' . now(),
            '記帳名稱：測試',
            '記帳金額：100',
        ];
        $user->notify(new LineNotify(
            implode("\r\n", $contents)
        ));

        return $this->response()->success();
    }
}
