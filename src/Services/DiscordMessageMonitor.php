<?php

namespace ADReece\LaracordLiveChat\Services;

use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DiscordMessageMonitor
{
    private DiscordService $discordService;
    private ChatService $chatService;

    public function __construct(DiscordService $discordService, ChatService $chatService)
    {
        $this->discordService = $discordService;
        $this->chatService = $chatService;
    }

    /**
     * Monitor all active Discord channels for new messages from agents
     */
    public function monitorActiveChannels(): void
    {
        $activeSessions = ChatSession::where('status', 'active')
            ->whereNotNull('discord_channel_id')
            ->get();

        foreach ($activeSessions as $session) {
            $this->monitorChannelForNewMessages($session);
        }
    }

    /**
     * Monitor a specific Discord channel for new messages
     */
    public function monitorChannelForNewMessages(ChatSession $session): void
    {
        if (!$session->discord_channel_id) {
            return;
        }

        $cacheKey = "discord_last_message_{$session->discord_channel_id}";
        $lastMessageId = Cache::get($cacheKey);

        // Get messages from Discord channel
        $messages = $this->discordService->getChannelMessages(
            $session->discord_channel_id,
            $lastMessageId
        );

        if (empty($messages)) {
            return;
        }

        // Process messages in chronological order (Discord returns newest first)
        $messages = array_reverse($messages);
        $botUserId = $this->getBotUserId();

        foreach ($messages as $message) {
            // Skip messages from the bot itself or embeds
            if ($message['author']['id'] === $botUserId || 
                !empty($message['embeds']) || 
                empty(trim($message['content']))) {
                continue;
            }

            // Skip if this message is already processed
            if ($lastMessageId && $message['id'] <= $lastMessageId) {
                continue;
            }

            // Create chat message from Discord message
            $this->createChatMessageFromDiscord($session, $message);

            // Update last processed message ID
            Cache::put($cacheKey, $message['id'], now()->addDays(7));
        }
    }

    /**
     * Create a chat message from a Discord message
     */
    private function createChatMessageFromDiscord(ChatSession $session, array $discordMessage): void
    {
        try {
            $agentName = $discordMessage['author']['global_name'] ?? 
                        $discordMessage['author']['username'] ?? 
                        'Support Agent';

            // Check if this message was already processed
            $existingMessage = ChatMessage::where('session_id', $session->id)
                ->where('discord_message_id', $discordMessage['id'])
                ->first();

            if ($existingMessage) {
                return;
            }

            $chatMessage = ChatMessage::create([
                'session_id' => $session->id,
                'sender_type' => 'agent',
                'sender_name' => $agentName,
                'message' => trim($discordMessage['content']),
                'discord_message_id' => $discordMessage['id'],
                'metadata' => [
                    'discord_user_id' => $discordMessage['author']['id'],
                    'discord_timestamp' => $discordMessage['timestamp'],
                ]
            ]);

            // Fire event for real-time updates
            event(new \ADReece\LaracordLiveChat\Events\MessageSent($chatMessage));

            Log::info('Agent message processed from Discord', [
                'session_id' => $session->id,
                'message_id' => $chatMessage->id,
                'agent_name' => $agentName
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process Discord message', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'discord_message_id' => $discordMessage['id']
            ]);
        }
    }

    /**
     * Get the bot's user ID (cached)
     */
    private function getBotUserId(): ?string
    {
        return Cache::remember('discord_bot_user_id', now()->addHours(24), function () {
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->get('https://discord.com/api/v10/users/@me', [
                    'headers' => [
                        'Authorization' => 'Bot ' . config('laracord-live-chat.discord.bot_token')
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody(), true);
                    return $data['id'] ?? null;
                }
            } catch (\Exception $e) {
                Log::error('Failed to get bot user ID', ['error' => $e->getMessage()]);
            }

            return null;
        });
    }

    /**
     * Process a single Discord message for a specific session
     */
    public function processDiscordMessage(string $sessionId, array $discordMessage): bool
    {
        try {
            $session = ChatSession::find($sessionId);
            if (!$session || !$session->isActive()) {
                return false;
            }

            $this->createChatMessageFromDiscord($session, $discordMessage);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to process single Discord message', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
            return false;
        }
    }
}
