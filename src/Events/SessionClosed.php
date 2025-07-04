<?php

namespace ADReece\LaracordLiveChat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ADReece\LaracordLiveChat\Models\ChatSession;

class SessionClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatSession $session;

    public function __construct(ChatSession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn()
    {
        return [
            new Channel('chat-sessions'),
            new Channel('chat-session.' . $this->session->id)
        ];
    }

    public function broadcastAs()
    {
        return 'session.closed';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->session->id,
            'status' => $this->session->status,
            'closed_at' => now()->toISOString(),
        ];
    }
}
