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
            $this->logAndReply('No events found', '無法找到事件');
            return;
        }

        $event = current($events);
        $lineUserId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        $social = $this->getSocialEntity($lineUserId);
        if (empty($social)) {
            $this->logAndReply('No social found', '無法找到對應的使用者，請確認您的帳號是否已綁定。', $replyToken);
            return;
        }

        $userId = $social->users->first()->id ?? null;
        if (is_null($userId)) {
            $this->logAndReply('User ID not found for social entity', '無法找到對應的使用者，請確認您的帳號是否已綁定。', $replyToken);
            return;
        }

        $messageType = $event['message']['type'] ?? null;
        if ($messageType != 'text') {
            $this->logAndReply('No text message found', '請傳送文字訊息', $replyToken);
            return;
        }

        $message = $event['message']['text'] ?? null;
        $lineService = app(LineService::class);

        if ($this->isWalletCommand($message)) {
            $this->handleWalletCommand($message, $replyToken, $userId, $social);
            return;
        }

        // selected wallet.code
        if ($this->isSelectedCommand($message)) {
            $this->handleSelectedCommand($message, $replyToken, $userId, $social);
            return;
        }

        $wallet = $this->getConnectedWallet($lineService, $userId, $social);
        if (empty($wallet)) {
            $this->sentMessage($replyToken, new TextMessageBuilder('請先選擇帳本'));
            return;
        }

        if ($this->isAddCommand($message)) {
            $this->handleAddCommand($message, $replyToken, $wallet, $userId);
        } elseif ($this->isCalculateCommand($message)) {
            $this->handleCalculateCommand($replyToken, $wallet);
        }
    }

    private function logAndReply($logMessage, $replyMessage, $replyToken = null)
    {
        Log::channel('bot')->info(sprintf("%s %s", get_class($this), $logMessage));
        if ($replyToken) {
            $this->sentMessage($replyToken, new TextMessageBuilder($replyMessage));
        }
    }

    private function getSocialEntity($lineUserId)
    {
        return SocialEntity::where('social_type', SocialType::SOCIAL_TYPE_LINE)
            ->where('social_type_value', $lineUserId)
            ->first();
    }

    private function isWalletCommand($message)
    {
        return Str::startsWith($message, '/wallets') || Str::contains($message, ['帳本', '列表']);
    }

    private function isSelectedCommand($message)
    {
        return Str::startsWith($message, '/selected');
    }

    private function handleSelectedCommand($message, $replyToken, $userId, $social)
    {
        $code = Str::after($message, '/selected ');
        $wallet = WalletEntity::where('code', $code)->first();
        if ($wallet) {
            app(LineService::class)->connectedWalletId($userId, $wallet->id);
            $message = '已選擇帳本: ' . $wallet->title;
            $social->wallet_id = $wallet->id;
            $social->save();
        } else {
            $message = '查無此帳本' . $code . '，請重新選擇';
        }
        $this->sentMessage($replyToken, new TextMessageBuilder($message));
    }

    private function handleWalletCommand($message, $replyToken, $userId, $social)
    {
        $wallets = app(WalletApiService::class)->getWalletByUserId($userId);
        $columns = [];

        foreach ($wallets as $wallet) {
            if (count($columns) >= 10) {
                break;
            }
            $actions = [new MessageTemplateActionBuilder("選擇此帳本", "/selected " . $wallet->code)];
            $columns[] = new ConfirmTemplateBuilder($wallet->title, $actions);
        }

        $carousel = new CarouselTemplateBuilder($columns);
        $this->sentMessage($replyToken, new TemplateMessageBuilder("請在手機中查看此訊息", $carousel));
    }

    private function getConnectedWallet($lineService, $userId, $social)
    {
        $walletId = $lineService->getConnectedWalletId($userId) ?? $social->wallet_id;
        return WalletEntity::find($walletId);
    }

    private function isAddCommand($message)
    {
        return Str::startsWith($message, 'add ') || Str::contains($message, '新增');
    }

    private function handleAddCommand($message, $replyToken, $wallet, $userId)
    {
        $message = str_replace(['add ', '新增'], '', $message);
        $message = trim($message);
        if (empty($message)) {
            $this->sentMessage($replyToken, new TextMessageBuilder('請輸入新增的內容'));
            return;
        }
        $this->generateAddWalletMessage($message, $replyToken, $wallet, $userId);
    }

    private function isCalculateCommand($message)
    {
        return Str::startsWith($message, '/calculate ') || Str::contains($message, '結算');
    }

    private function handleCalculateCommand($replyToken, $wallet)
    {
        $walletApiService = app(WalletApiService::class);
        $messages = $walletApiService->calculateAndNotifyWalletExpenses($wallet);
        $this->sentMessage($replyToken, new TextMessageBuilder(implode("\r\n", $messages)));
    }

    private function generateAddWalletMessage($userMessage, $replyToken, $wallet, $userId)
    {
        $messages = [];
        $categories = CategoryEntity::select(['id', 'name'])->get();
        $messages[] = '以下是帳本的 category，請幫我分析後續的內容分別為哪個分類，格式為: categoryId=${categoryId}, amount=${amount}, title=${title}。如果遇到分析難度過高，例如是食物，請根據現在時間判斷是否為早午晚餐。 現在時間為：' . now()->format('Y-m-d H:i:s') . '。';
        $messages[] = '分類資料: ' . $categories->map(fn($cat) => "id={$cat->id}, name={$cat->name}")->implode('; ');
        $messages[] = $userMessage;

        $messages = array_map(function ($message) {
            return [
                'role' => 'user',
                'content' => $message
            ];
        }, $messages);

        $geminiService = app(GeminiService::class);
        $response = $geminiService->getChatResult($messages);
        // 清理回應內容，確保格式正確
        $cleanResponse = trim(str_replace(['`'], '', $response));
        if (empty($cleanResponse)) {
            Log::channel('bot')->error('GeminiService 回應為空');
            return;
        }
        // categoryId=2, amount=100, title=熊貓外送
        // 這裡可以使用正則表達式來解析回應
        // 例如：categoryId=2, amount=100, title=熊貓外送
        // 這裡假設回應格式為 "categoryId=2, amount=100, title=熊貓外送"
        // 使用正則表達式來解析回應
        preg_match('/categoryId=(\d+), amount=(\d+), title=([^,]+)/', $cleanResponse, $matches);
        if (count($matches) < 4) {
            Log::channel('bot')->error('無法解析回應: ' . $cleanResponse);
            $this->sentMessage($replyToken, new TextMessageBuilder('無法解析回應，請檢查格式'));
            return;
        }
        $categoryId = $matches[1];
        $amount = $matches[2];
        $title = $matches[3];
        $category = $categories->where('id', $categoryId)->first();
        $categoryName = $category ? $category->name : '未知分類';
        $jsonData['categoryName'] = $categoryName;
        $jsonData['amount'] = $amount;
        $jsonData['title'] = $title;
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
