<?php

namespace App\Observers;

use App\Models\Message;
use App\Notifications\NewMessageNotification;

class MessageObserver
{
    public function created(Message $message): void
    {
        if ($message->to_user_id && $message->recipient) {
            $message->recipient->notify(new NewMessageNotification($message));
        }
    }
}
