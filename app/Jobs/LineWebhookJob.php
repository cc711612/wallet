<?php

namespace App\Jobs;

use App\Models\Categories\Entities\CategoryEntity;
use App\Models\Socials\Contracts\Constants\SocialType;
use App\Models\Socials\Databases\Entities\SocialEntity;
use App\Models\Socials\Databases\Services\LineService;
use App\Models\Wallets\Databases\Entities\WalletEntity;
use App\Models\Wallets\Databases\Services\WalletApiService;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

class LineWebhookJob implements ShouldQueue
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
        $this
            ->onQueue('send_message');
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $events = $this->params['events'] ?? [];
        if (empty($events)) {
            Log::channel('bot')->info(sprintf("%s No events found", get_class($this)));
            return;
        }
        $event = current($events);
        $lineUserId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;
        $social = SocialEntity::where('social_type', SocialType::SOCIAL_TYPE_LINE)
            ->where('social_type_value', $lineUserId)
            ->first();
        if (empty($social)) {
            Log::channel('bot')->info(sprintf("%s No social found", get_class($this)));
            $this->sentMessage($replyToken, new TextMessageBuilder('無法找到對應的使用者，請確認您的帳號是否已綁定。'));
            return;
        }
        $userId = $social->users->first()->id ?? null;

        if (is_null($userId)) {
            Log::channel('bot')->error(sprintf("%s User ID not found for social entity", get_class($this)));
            $this->sentMessage($replyToken, new TextMessageBuilder('無法找到對應的使用者，請確認您的帳號是否已綁定。'));
            return;
        }

        $messageType = $event['message']['type'] ?? null;
        if ($messageType != 'text') {
            Log::channel('bot')->info(sprintf("%s No text message found", get_class($this)));
            $this->sentMessage($replyToken, new TextMessageBuilder('請傳送文字訊息'));
            return;
        }
        $message = $event['message']['text'] ?? null;
        /**
         * @var LineService $lineService
         */
        $lineService = app(LineService::class);

        if (Str::startsWith($message, '/wallets')) {
            $wallets = app(WalletApiService::class)
                ->getWalletByUserId($userId);
            $columns = [];

            foreach ($wallets as $wallet) {
                if (count($columns) >= 10) {
                    break; // 限制最多 10 個項目
                }
                $actions = array(
                    new MessageTemplateActionBuilder("選擇此帳本", "/selected " . $wallet->code),
                );
                $column = new ConfirmTemplateBuilder($wallet->title, $actions);
                $columns[] = $column;
            }
            $carousel = new CarouselTemplateBuilder($columns);
            $textMessageBuilder = new TemplateMessageBuilder("請在手機中查看此訊息", $carousel);
            $this->sentMessage($replyToken, $textMessageBuilder);
            return;
        }

        // /selected
        if (Str::startsWith($message, '/selected ')) {
            $code = Str::after($message, '/selected ');
            $wallet = WalletEntity::where('code', $code)->first();
            $message = '查無此帳本' . $code . '，請重新選擇';
            if ($wallet) {
                $lineService->connectedWalletId($userId, $wallet->id);
                $message = '已選擇帳本: ' . $wallet->title;
                $social->wallet_id = $wallet->id;
                $social->save();
            }
            
            $this->sentMessage($replyToken, new TextMessageBuilder($message));
            return;
        }
        // 假設 cache 沒有的話
        $walletId = $lineService->getConnectedWalletId($userId) ?? $social->wallet_id;
        $wallet = WalletEntity::find($walletId);

        if (empty($wallet)) {
            $this->sentMessage($replyToken, new TextMessageBuilder('請先選擇帳本'));
            return;
        }

        if (Str::startsWith($message, '/add ')) {
            $message = Str::after($message, '/add ');
            $this->generateAddWalletMessage($message, $replyToken, $wallet, $userId);
        }

        if (Str::startsWith($message, '/calculate ') || Str::contains($message, '結算')) {
            /**
             * @var WalletApiService $walletApiService
             */
            $walletApiService = app(WalletApiService::class);
            $messages = $walletApiService->calculateAndNotifyWalletExpenses($wallet); 
            $this->sentMessage($replyToken, new TextMessageBuilder(implode("\r\n", $messages)));
        }
    }

    private function generateAddWalletMessage($userMessage, $replyToken, $wallet, $userId)
    {
        $messages = [];
        $categories = CategoryEntity::select(['id', 'name'])->get();
        $messages[] = '以下是帳本的 category 以 json 方式告訴你:' . json_encode($categories) . '請幫我分析後續的內容分別為哪個分類透過 json 告訴我, ex : categoryId: ${categoryId}, amount: ${amount}, title:${title}; 如果遇到分析難度過高,如是食物,請幫我根據現在時間來判斷是否為早午晚餐,不用回傳除了json之外的東西';
        $messages[] = $userMessage;
        $messages = array_map(function ($message) {
            return [
                'role' => 'user',
                'content' => $message
            ];
        }, $messages);
        $geminiService = app(GeminiService::class);
        $response = $geminiService->getChatResult($messages);
        // 去掉 "json "，保留 { 開頭的部分
        $cleanJson = str_replace(['json', '`'], ['', ''], $response);
        // 去掉多餘的空格和換行
        // 將字串轉換為 JSON 格式
        $jsonData = json_decode($cleanJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('bot')->error('JSON decode error: ' . json_last_error_msg());
            return;
        }
        $categoryId = $jsonData['categoryId'] ?? null;
        $category = $categories->where('id', $categoryId)->first();
        $categoryName = $category ? $category->name : '未知分類';
        $jsonData['categoryName'] = $categoryName;
        // queue create wallet detail
        CreateWalletDetailJob::dispatch($userId, $wallet->id, $jsonData);
        // 回傳欄位的資料 根據 \n 來換行
        $columns = [];
        $actions = array(
            new MessageTemplateActionBuilder("完全正確", "完全正確 :" . $jsonData['title']),
            new MessageTemplateActionBuilder("錯誤資訊", "錯誤資訊 :" . $jsonData['title']),
        );
        $message = '已根據內容分析出來的分類: ' . "\n";
        foreach ($jsonData as $key => $value) {
            $message .= $key . ': ' . $value . "\n";
        }
        $columns[] = new ConfirmTemplateBuilder($message, $actions);

        $carousel = new CarouselTemplateBuilder($columns);
        $textMessageBuilder = new TemplateMessageBuilder("請在手機中查看此訊息", $carousel);

        $this->sentMessage($replyToken, $textMessageBuilder);
    }

    /**
     * Send message to line bot
     * @param string $replayToken
     * @param MessageBuilder $message
     * @return void
     */
    private function sentMessage($replayToken, MessageBuilder $message)
    {
        try {
            //實體化line bot物件
            $httpClient = new CurlHTTPClient(config('bot.line.access_token'));
            $bot = new LINEBot($httpClient, ['channelSecret' => config('bot.line.channel_secret')]);
            $bot->replyMessage($replayToken, $message);
            Log::channel('bot')->info(sprintf(
                "%s SUCCESS params : %s",
                get_class($this),
                json_encode($this->params, JSON_UNESCAPED_UNICODE)
            ));
        } catch (\Exception $exception) {
            Log::channel('bot')->error(sprintf(
                "%s Error params : %s",
                get_class($this),
                json_encode($exception, JSON_UNESCAPED_UNICODE)
            ));
        }
    }
}
