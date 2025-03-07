<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

     public $message;
     public $chatId;
    public function __construct($message)
    {
        $this->message = $this->message->message;
        $this->chatId = $this->message->chat_id;
        \Log::info('New message event fired', [
            'chatId' => $this->message->chat_id,
            'message' => $this->message->message
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */

  public function broadcastOn()
    {
        return new Channel('chat.' . $this->chatId); // Channel specific to chat_id
    }

    public function broadcastAs() {
        return 'message.sent';
    }
    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'chatId' => $this->chatId,
        ];
    }
}
