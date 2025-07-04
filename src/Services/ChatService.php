<?php

namespace ADReece\LaracordLiveChat\Services;

use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Events\MessageSent;
use ADReece\LaracordLiveChat\Events\SessionStarted;
use ADReece\LaracordLiveChat\Events\SessionClosed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class ChatService
{
    private DiscordService $discordService;

    public function __construct(DiscordService $discordService)
    {
        $this->discordService = $discordService;
    }

    /**
     * Create a new chat session
     */
    public function createSession(array $data): ChatSession
    {
        $session = ChatSession::create([
            'customer_name' => $data['name'] ?? null,
            'customer_email' => $data['email'] ?? null,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'] ?? null,
            'status' => 'active',
            'metadata' => $data['metadata'] ?? [],
        ]);

        // Create Discord channel for this session
        $channelId = $this->discordService->createChatChannel($session);
        if ($channelId) {
            $session->update(['discord_channel_id' => $channelId]);
        }

        // Fire event
        Event::dispatch(new SessionStarted($session));

        return $session;
    }

    /**
     * Send a message from customer
     */
    public function sendCustomerMessage(string $sessionId, string $message, ?string $customerName = null): ChatMessage
    {
        $session = ChatSession::findOrFail($sessionId);

        // Update customer name if provided and not already set
        if ($customerName && !$session->customer_name) {
            $session->update(['customer_name' => $customerName]);
        }

        $chatMessage = $session->messages()->create([
            'sender_type' => 'customer',
            'sender_name' => $customerName ?: $session->customer_name,
            'message' => $message,
        ]);

        // Send to Discord
        $this->discordService->sendNewChatMessage($session, $chatMessage);

        // Fire event
        Event::dispatch(new MessageSent($chatMessage));

        return $chatMessage;
    }

    /**
     * Send a message from agent (via Discord)
     */
    public function sendAgentMessage(string $sessionId, string $message, string $agentName = 'Support Agent'): ChatMessage
    {
        $session = ChatSession::findOrFail($sessionId);

        $chatMessage = $session->messages()->create([
            'sender_type' => 'agent',
            'sender_name' => $agentName,
            'message' => $message,
        ]);

        // Fire event
        Event::dispatch(new MessageSent($chatMessage));

        return $chatMessage;
    }

    /**
     * Get session with messages
     */
    public function getSession(string $sessionId): ?ChatSession
    {
        return ChatSession::with(['messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->find($sessionId);
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions()
    {
        return ChatSession::where('status', 'active')
            ->with('latestMessage')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Close a chat session
     */
    public function closeSession(string $sessionId): bool
    {
        $session = ChatSession::findOrFail($sessionId);
        
        if ($session->isActive()) {
            $session->close();

            // Delete Discord channel
            $this->discordService->deleteChatChannel($session);

            // Fire event
            Event::dispatch(new SessionClosed($session));

            return true;
        }

        return false;
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(string $sessionId, string $senderType = 'customer'): void
    {
        ChatMessage::where('session_id', $sessionId)
            ->where('sender_type', $senderType)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Check if session exists and is active
     */
    public function isSessionActive(string $sessionId): bool
    {
        return ChatSession::where('id', $sessionId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(string $sessionId): array
    {
        $session = ChatSession::withCount('messages')->findOrFail($sessionId);

        return [
            'total_messages' => $session->messages_count,
            'customer_messages' => $session->messages()->where('sender_type', 'customer')->count(),
            'agent_messages' => $session->messages()->where('sender_type', 'agent')->count(),
            'duration' => $session->created_at->diffForHumans(now(), true),
            'status' => $session->status,
        ];
    }

    /**
     * Clean up old sessions and messages
     */
    public function cleanup(): void
    {
        $sessionLifetime = config('laracord-live-chat.storage.session_lifetime', 24);
        $messageRetention = config('laracord-live-chat.storage.message_retention', 30);

        // Close old active sessions
        ChatSession::where('status', 'active')
            ->where('created_at', '<', now()->subHours($sessionLifetime))
            ->update(['status' => 'closed']);

        // Delete old messages
        ChatMessage::where('created_at', '<', now()->subDays($messageRetention))
            ->delete();

        // Delete old closed sessions
        ChatSession::where('status', 'closed')
            ->where('updated_at', '<', now()->subDays($messageRetention))
            ->delete();
    }
}
