<?php

namespace Swoopy\LaracordLiveChat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Swoopy\LaracordLiveChat\Models\ChatSession;

class SessionStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatSession $session;

    public function __construct(ChatSession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn()
    {
        return new Channel('chat-sessions');
    }

    public function broadcastAs()
    {
        return 'session.started';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->session->id,
            'customer_name' => $this->session->customer_name,
            'customer_email' => $this->session->customer_email,
            'status' => $this->session->status,
            'created_at' => $this->session->created_at->toISOString(),
        ];
    }
}
