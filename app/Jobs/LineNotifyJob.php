<?php

namespace App\Jobs;

use App\Models\Users\Databases\Entities\UserEntity;
use App\Notifications\LineNotify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;


class LineNotifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    private $userId;
    /**
     * @var
     */
    private $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $message)
    {
        //
        $this
            ->onQueue('send_message');
        $this->userId = $userId;
        $this->message = $message;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $user = UserEntity::find($this->userId);
            if ($user->notify_token) {
                $user->notify(new LineNotify($this->message));

                Log::channel('bot')->info(
                    'Line Notify Success',
                    [
                        'user_id' => $this->userId,
                        'message' => $this->message
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel('bot')->error(sprintf(
                "%s Error params : %s",
                get_class($this),
                json_encode($exception, JSON_UNESCAPED_UNICODE)
            ));
        }
    }
}
