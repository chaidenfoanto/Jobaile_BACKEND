<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Event\ShouldQueue;
use App\Models\ChatModel;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;

    public function __construct(ChatModel $chat)
    {
        $this->chat = $chat;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('chat.' . $this->chat->id_recruiter . '.' . $this->chat->id_worker);
    }

    public function broadcastAs(): string
    {
        return 'chat.sent';
    }
}
