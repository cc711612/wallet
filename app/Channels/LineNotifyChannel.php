<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Phattarachai\LineNotify\Facade\Line;

class LineNotifyChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toLineNotify($notifiable);

        Line::setToken($notifiable->notify_token)->send($message);
    }
}