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
     * @var array $params 任務參數
     */
    private $params;

    /**
     * 建構子：初始化任務
     *
     * @param array $params
     */
    public function __construct($params)
    {
        $this->onQueue('send_message'); // 指定任務隊列
        $this->params = $params;
    }

    /**
     * 執行任務的主方法
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
        if ($messageType !== 'text') {
            $this->logAndReply('No text message found', '請傳送文字訊息', $replyToken);
            return;
        }

        $message = $event['message']['text'] ?? null;
        $lineService = app(LineService::class);

        // 判斷訊息類型並執行對應的處理邏輯
        if ($this->isWalletCommand($message)) {
            $this->handleWalletCommand($message, $replyToken, $userId, $social);
        } elseif ($this->isSelectedCommand($message)) {
            $this->handleSelectedCommand($message, $replyToken, $userId, $social);
        } else {
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
    }

    /**
     * 紀錄日誌並回覆訊息
     */
    private function logAndReply($logMessage, $replyMessage, $replyToken = null)
    {
        Log::channel('bot')->info(sprintf("%s %s", __CLASS__, $logMessage));
        if ($replyToken) {
            $this->sentMessage($replyToken, new TextMessageBuilder($replyMessage));
        }
    }

    /**
     * 根據 LINE 使用者 ID 獲取對應的 SocialEntity
     */
    private function getSocialEntity($lineUserId)
    {
        return SocialEntity::where('social_type', SocialType::SOCIAL_TYPE_LINE)
            ->where('social_type_value', $lineUserId)
            ->first();
    }

    /**
     * 判斷是否為帳本指令
     */
    private function isWalletCommand($message)
    {
        return Str::startsWith($message, '/wallets') || Str::contains($message, ['帳本', '列表']);
    }

    /**
     * 判斷是否為選擇帳本指令
     */
    private function isSelectedCommand($message)
    {
        return Str::startsWith($message, '/selected');
    }

    /**
     * 處理選擇帳本指令
     */
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

    /**
     * 處理帳本列表指令
     */
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

    /**
     * 獲取使用者已連結的帳本
     */
    private function getConnectedWallet($lineService, $userId, $social)
    {
        $walletId = $lineService->getConnectedWalletId($userId) ?? $social->wallet_id;
        return WalletEntity::find($walletId);
    }

    /**
     * 判斷是否為新增指令
     */
    private function isAddCommand($message)
    {
        $checkMsg = strtolower($message);
        return Str::startsWith($checkMsg, 'add') || Str::contains($checkMsg, '新增');
    }

    /**
     * 處理新增指令
     */
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

    /**
     * 判斷是否為結算指令
     */
    private function isCalculateCommand($message)
    {
        return Str::startsWith($message, '/calculate ') || Str::contains($message, '結算');
    }

    /**
     * 處理結算指令
     */
    private function handleCalculateCommand($replyToken, $wallet)
    {
        $walletApiService = app(WalletApiService::class);
        $messages = $walletApiService->calculateAndNotifyWalletExpenses($wallet);
        $this->sentMessage($replyToken, new TextMessageBuilder(implode("\r\n", $messages)));
    }

    /**
     * 生成新增帳本的訊息
     */
    private function generateAddWalletMessage($userMessage, $replyToken, $wallet, $userId)
    {
        $categories = CategoryEntity::select(['id', 'name'])->get();
        $categoryList = $categories->map(fn($cat) => "id={$cat->id}, name={$cat->name}")->implode('; ');

        // 將系統指令與使用者輸入合併為單一 user 訊息，避免 multi-turn 角色順序問題
        $prompt = implode("\n", [
            '你是一個記帳分析助手，請嚴格依照格式回傳，不要有任何多餘說明。',
            '現在時間：' . now()->format('Y-m-d H:i:s'),
            '可用分類（請從中選擇最接近的一項）：' . $categoryList,
            '請分析以下內容，回傳純 JSON 格式（不要用 markdown code block）：',
            '{"categoryId": <數字>, "amount": <整數>, "title": "<名稱>"}',
            '使用者輸入：' . $userMessage,
        ]);

        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $geminiService = app(GeminiService::class);

        try {
            $response = $geminiService->getChatResult($messages);
        } catch (\Exception $e) {
            Log::channel('bot')->error('GeminiService 呼叫失敗: ' . $e->getMessage());
            $this->sentMessage($replyToken, new TextMessageBuilder('AI 服務暫時無法使用，請稍後再試'));
            return;
        }

        // 清理回應：移除 markdown code block 標記與多餘空白
        $cleanResponse = trim(preg_replace('/```(?:json)?|```/', '', $response));

        if (empty($cleanResponse)) {
            Log::channel('bot')->error('GeminiService 回應為空');
            $this->sentMessage($replyToken, new TextMessageBuilder('AI 無回應，請稍後再試'));
            return;
        }

        // 優先嘗試 JSON 解析
        $parsed = json_decode($cleanResponse, true);

        // 若 JSON 解析失敗，再 fallback 到 Regex（容錯空格與小數金額）
        if (json_last_error() !== JSON_ERROR_NONE || empty($parsed)) {
            Log::channel('bot')->warning('JSON 解析失敗，嘗試 Regex fallback', ['response' => $cleanResponse]);
            if (preg_match('/categoryId\s*=\s*(\d+)[,\s]+amount\s*=\s*([\d.]+)[,\s]+title\s*=\s*(.+)/i', $cleanResponse, $matches)) {
                $parsed = [
                    'categoryId' => (int) $matches[1],
                    'amount'     => (int) round((float) $matches[2]),
                    'title'      => trim($matches[3]),
                ];
            } else {
                Log::channel('bot')->error('無法解析 Gemini 回應: ' . $cleanResponse);
                $this->sentMessage($replyToken, new TextMessageBuilder('無法解析 AI 回應，請嘗試更明確的描述，例如「午餐 120」'));
                return;
            }
        }

        $categoryId = (int) ($parsed['categoryId'] ?? 0);
        $amount     = (int) round((float) ($parsed['amount'] ?? 0));
        $title      = trim($parsed['title'] ?? '');

        // 驗證必要欄位
        if ($categoryId <= 0 || $amount <= 0 || empty($title)) {
            Log::channel('bot')->error('Gemini 回傳欄位不完整', ['parsed' => $parsed]);
            $this->sentMessage($replyToken, new TextMessageBuilder('解析結果不完整，請重新輸入'));
            return;
        }

        $category     = $categories->where('id', $categoryId)->first();
        $categoryName = $category ? $category->name : '未知分類';

        $jsonData = [
            'categoryId'   => $categoryId,
            'categoryName' => $categoryName,
            'amount'       => $amount,
            'title'        => $title,
        ];

        // 確認解析正確後才派發 Job
        CreateWalletDetailJob::dispatch($userId, $wallet->id, $jsonData);

        // 回傳確認訊息
        $message = "已分析出以下記帳資料：\n"
            . "分類：{$categoryName}\n"
            . "金額：{$amount}\n"
            . "名稱：{$title}\n"
            . "請確認是否正確？";

        $actions = [
            new MessageTemplateActionBuilder("完全正確", "完全正確 :" . $title),
            new MessageTemplateActionBuilder("錯誤資訊", "錯誤資訊 :" . $title),
        ];

        $columns   = [new ConfirmTemplateBuilder($message, $actions)];
        $carousel  = new CarouselTemplateBuilder($columns);
        $textMessageBuilder = new TemplateMessageBuilder("請在手機中查看此訊息", $carousel);

        $this->sentMessage($replyToken, $textMessageBuilder);
    }

    /**
     * 發送訊息給 LINE Bot
     */
    private function sentMessage($replyToken, MessageBuilder $message)
    {
        try {
            //實體化line bot物件
            $httpClient = new CurlHTTPClient(config('bot.line.access_token'));
            $bot = new LINEBot($httpClient, ['channelSecret' => config('bot.line.channel_secret')]);
            $bot->replyMessage($replyToken, $message);
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
