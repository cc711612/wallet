<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class SendLineMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    private $userIds; // 修改為小駝峰
    /**
     * @var
     */
    private $message; // 保持不變
    /**
     * @var
     */
    private $params; // 保持不變

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        //
        $this
            ->onQueue('send_message');
        $userIds = config('bot.line.admin_user_ids'); // 修改為小駝峰
        if (is_null(Arr::get($params, 'user_id')) === false) {
            $userIds = array_push($userIds, Arr::get($params, 'user_id')); // 修改為小駝峰
        }
        $this->userIds = $userIds; // 修改為小駝峰
        $this->message = Arr::get($params, 'message');
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            //實體化line bot物件
            $httpClient = new CurlHTTPClient(config('bot.line.access_token'));
            $bot = new LINEBot($httpClient, ['channelSecret' => config('bot.line.channel_secret')]);
            /**
             * "userId" => "U1d40789aa8461e74ead62181b1abc442"
             * "displayName" => "冠融"
             * "pictureUrl" => "https://sprofile.line-scdn.net/0hE0DiZsyUGh9pEDa-y8xkYBlAGXVKYUMNQ3YCLFtCTXtRKQ8cTCVQfg4QTSkAIg0aEHZcKVsZFy1lA215d0bmK24gRChQJ1VNQ35T_g"
             * "language" => "zh-TW"
             */
            foreach ($this->userIds as $userId) { // 修改為小駝峰
                $user_info = $this->jsonToArray($bot->getProfile($userId)->getRawBody()); // 修改為小駝峰
                $bot->pushMessage($userId, // 修改為小駝峰
                    new TextMessageBuilder(sprintf("%s 您好 網站發生錯誤:\n%s", Arr::get($user_info, 'displayName'),
                        $this->message)));
                Log::channel('bot')->info(sprintf("%s SUCCESS params : %s", get_class($this),
                    json_encode($this->params, JSON_UNESCAPED_UNICODE)));
            }
        } catch (\Exception $exception) {
            Log::channel('bot')->info(sprintf("%s Error params : %s", get_class($this),
                json_encode($exception, JSON_UNESCAPED_UNICODE)));
        }
    }

    /**
     * @param  string  $json
     *
     * @return mixed
     */
    private function jsonToArray(string $json)
    {
        return json_decode($json, 1);
    }
}
