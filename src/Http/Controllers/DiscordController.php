<?php

namespace Swoopy\LaracordLiveChat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Swoopy\LaracordLiveChat\Services\ChatService;

class DiscordController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Handle Discord bot commands via webhook
     */
    public function handleWebhook(Request $request)
    {
        // Verify the request is from Discord (implement signature verification if needed)
        
        $data = $request->all();
        
        // Handle different Discord event types
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 1: // PING
                    return response()->json(['type' => 1]);
                    
                case 2: // APPLICATION_COMMAND
                    return $this->handleSlashCommand($data);
                    
                case 3: // MESSAGE_COMPONENT
                    return $this->handleMessageComponent($data);
            }
        }

        return response()->json(['message' => 'Unknown event type'], 400);
    }

    /**
     * Handle slash commands
     */
    private function handleSlashCommand(array $data)
    {
        $command = $data['data']['name'] ?? '';
        
        switch ($command) {
            case 'sessions':
                return $this->handleSessionsCommand($data);
                
            case 'close':
                return $this->handleCloseCommand($data);
                
            default:
                return response()->json([
                    'type' => 4,
                    'data' => [
                        'content' => 'Unknown command'
                    ]
                ]);
        }
    }

    /**
     * Handle sessions command: /sessions
     */
    private function handleSessionsCommand(array $data): \Illuminate\Http\JsonResponse
    {
        $sessions = $this->chatService->getActiveSessions();

        if ($sessions->isEmpty()) {
            return response()->json([
                'type' => 4,
                'data' => [
                    'content' => 'No active chat sessions',
                    'flags' => 64 // Ephemeral
                ]
            ]);
        }

        $sessionList = $sessions->map(function ($session) {
            $lastMessage = $session->latestMessage;
            $lastMessageTime = $lastMessage ? $lastMessage->created_at->diffForHumans() : 'No messages';
            $channelMention = $session->discord_channel_id ? "<#{$session->discord_channel_id}>" : 'No channel';
            
            return sprintf(
                "**%s** (ID: `%s`)\n├ Channel: %s\n├ Customer: %s\n├ Last message: %s\n└ Duration: %s",
                $session->customer_name ?: 'Anonymous',
                $session->id,
                $channelMention,
                $session->customer_email ?: 'No email',
                $lastMessageTime,
                $session->created_at->diffForHumans()
            );
        })->join("\n\n");

        return response()->json([
            'type' => 4,
            'data' => [
                'embeds' => [
                    [
                        'title' => '💬 Active Chat Sessions',
                        'description' => $sessionList,
                        'color' => 3447003,
                        'footer' => [
                            'text' => 'Reply by typing messages in the respective channel'
                        ]
                    ]
                ],
                'flags' => 64 // Ephemeral
            ]
        ]);
    }

    /**
     * Handle close command: /close <session_id>
     */
    private function handleCloseCommand(array $data): \Illuminate\Http\JsonResponse
    {
        $options = $data['data']['options'] ?? [];
        $sessionId = null;
        
        foreach ($options as $option) {
            if ($option['name'] === 'session_id') {
                $sessionId = $option['value'];
                break;
            }
        }

        if (!$sessionId) {
            return response()->json([
                'type' => 4,
                'data' => [
                    'content' => 'Usage: /close <session_id>',
                    'flags' => 64 // Ephemeral
                ]
            ]);
        }

        try {
            $closed = $this->chatService->closeSession($sessionId);

            if ($closed) {
                return response()->json([
                    'type' => 4,
                    'data' => [
                        'content' => "✅ Session `{$sessionId}` has been closed",
                        'flags' => 64 // Ephemeral
                    ]
                ]);
            } else {
                return response()->json([
                    'type' => 4,
                    'data' => [
                        'content' => "❌ Session not found or already closed",
                        'flags' => 64 // Ephemeral
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'type' => 4,
                'data' => [
                    'content' => "❌ Error closing session",
                    'flags' => 64 // Ephemeral
                ]
            ]);
        }
    }

    /**
     * Handle message component interactions (buttons, etc.)
     */
    private function handleMessageComponent(array $data)
    {
        // Handle button clicks and other component interactions
        return response()->json([
            'type' => 4,
            'data' => [
                'content' => 'Component interaction received',
                'flags' => 64 // Ephemeral
            ]
        ]);
    }
}
