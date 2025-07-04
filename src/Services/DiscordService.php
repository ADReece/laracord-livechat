<?php

namespace Swoopy\LaracordLiveChat\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Swoopy\LaracordLiveChat\Models\ChatSession;
use Swoopy\LaracordLiveChat\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    private Client $client;
    private string $webhookUrl;
    private ?string $botToken;

    public function __construct(string $webhookUrl, ?string $botToken = null)
    {
        $this->client = new Client();
        $this->webhookUrl = $webhookUrl;
        $this->botToken = $botToken;
    }

    /**
     * Create a dedicated Discord channel for a chat session
     */
    public function createChatChannel(ChatSession $session): ?string
    {
        if (!$this->botToken) {
            Log::error('Discord bot token not configured for channel creation');
            return null;
        }

        $guildId = config('laracord-live-chat.discord.guild_id');
        $categoryId = config('laracord-live-chat.discord.category_id');

        if (!$guildId) {
            Log::error('Discord guild ID not configured');
            return null;
        }

        try {
            $customerName = $session->customer_name ? 
                preg_replace('/[^a-zA-Z0-9-_]/', '', strtolower($session->customer_name)) : 
                'anonymous';
            
            $channelName = "chat-{$customerName}-" . substr($session->id, 0, 8);

            $channelData = [
                'name' => $channelName,
                'type' => 0, // GUILD_TEXT
                'topic' => "Live chat with {$session->customer_name} ({$session->customer_email}) - Session: {$session->id}",
            ];

            if ($categoryId) {
                $channelData['parent_id'] = $categoryId;
            }

            $response = $this->client->post("https://discord.com/api/v10/guilds/{$guildId}/channels", [
                'headers' => [
                    'Authorization' => 'Bot ' . $this->botToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $channelData
            ]);

            if ($response->getStatusCode() === 201) {
                $data = json_decode($response->getBody(), true);
                $channelId = $data['id'];

                // Send initial message to the channel
                $this->sendInitialChannelMessage($channelId, $session);

                return $channelId;
            }

            return null;
        } catch (RequestException $e) {
            Log::error('Failed to create Discord channel', [
                'error' => $e->getMessage(),
                'session_id' => $session->id
            ]);
            return null;
        }
    }

    /**
     * Send initial message to the newly created channel
     */
    private function sendInitialChannelMessage(string $channelId, ChatSession $session): void
    {
        try {
            $embed = [
                'title' => '� New Chat Session Started',
                'color' => 65280, // Green
                'fields' => [
                    [
                        'name' => 'Customer',
                        'value' => $session->customer_name ?: 'Anonymous',
                        'inline' => true
                    ],
                    [
                        'name' => 'Email',
                        'value' => $session->customer_email ?: 'Not provided',
                        'inline' => true
                    ],
                    [
                        'name' => 'Session ID',
                        'value' => "`{$session->id}`",
                        'inline' => true
                    ],
                    [
                        'name' => 'IP Address',
                        'value' => $session->ip_address,
                        'inline' => true
                    ],
                    [
                        'name' => 'Started',
                        'value' => $session->created_at->format('Y-m-d H:i:s T'),
                        'inline' => true
                    ],
                    [
                        'name' => 'Instructions',
                        'value' => 'Simply type your messages in this channel to reply to the customer. The channel will be automatically deleted when the chat is closed.',
                        'inline' => false
                    ]
                ],
                'timestamp' => $session->created_at->toISOString()
            ];

            $this->client->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                'headers' => [
                    'Authorization' => 'Bot ' . $this->botToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'embeds' => [$embed]
                ]
            ]);
        } catch (RequestException $e) {
            Log::error('Failed to send initial channel message', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId
            ]);
        }
    }

    /**
     * Send a new chat message to the dedicated Discord channel
     */
    public function sendNewChatMessage(ChatSession $session, ChatMessage $message): bool
    {
        if (!$session->discord_channel_id) {
            Log::error('No Discord channel found for session', ['session_id' => $session->id]);
            return false;
        }

        try {
            $embed = [
                'color' => 3447003, // Blue
                'fields' => [
                    [
                        'name' => '💬 Customer Message',
                        'value' => $this->truncateMessage($message->message),
                        'inline' => false
                    ]
                ],
                'timestamp' => $message->created_at->toISOString(),
                'footer' => [
                    'text' => "From: {$message->sender_name}"
                ]
            ];

            $response = $this->client->post("https://discord.com/api/v10/channels/{$session->discord_channel_id}/messages", [
                'headers' => [
                    'Authorization' => 'Bot ' . $this->botToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'embeds' => [$embed]
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            Log::error('Failed to send message to Discord channel', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'channel_id' => $session->discord_channel_id
            ]);
            return false;
        }
    }

    /**
     * Send a session started notification to Discord
     */
    public function sendSessionStarted(ChatSession $session): bool
    {
        try {
            $embed = [
                'title' => '🟢 New Chat Session Started',
                'color' => 65280, // Green
                'fields' => [
                    [
                        'name' => 'Customer',
                        'value' => $session->customer_name ?: 'Anonymous',
                        'inline' => true
                    ],
                    [
                        'name' => 'Session ID',
                        'value' => $session->id,
                        'inline' => true
                    ],
                    [
                        'name' => 'IP Address',
                        'value' => $session->ip_address,
                        'inline' => true
                    ]
                ],
                'timestamp' => $session->created_at->toISOString()
            ];

            if ($session->customer_email) {
                $embed['fields'][] = [
                    'name' => 'Email',
                    'value' => $session->customer_email,
                    'inline' => true
                ];
            }

            $response = $this->client->post($this->webhookUrl, [
                'json' => [
                    'embeds' => [$embed]
                ]
            ]);

            return $response->getStatusCode() === 204;
        } catch (RequestException $e) {
            Log::error('Failed to send Discord webhook for session start', [
                'error' => $e->getMessage(),
                'session_id' => $session->id
            ]);
            return false;
        }
    }

    /**
     * Delete the Discord channel when chat session is closed
     */
    public function deleteChatChannel(ChatSession $session): bool
    {
        if (!$this->botToken || !$session->discord_channel_id) {
            return false;
        }

        try {
            // Send a closing message first
            $embed = [
                'title' => '🔴 Chat Session Closed',
                'color' => 16711680, // Red
                'fields' => [
                    [
                        'name' => 'Session Duration',
                        'value' => $this->formatDuration($session->created_at, now()),
                        'inline' => true
                    ],
                    [
                        'name' => 'Total Messages',
                        'value' => $session->messages()->count(),
                        'inline' => true
                    ]
                ],
                'description' => 'This channel will be deleted in 30 seconds.',
                'timestamp' => now()->toISOString()
            ];

            $this->client->post("https://discord.com/api/v10/channels/{$session->discord_channel_id}/messages", [
                'headers' => [
                    'Authorization' => 'Bot ' . $this->botToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'embeds' => [$embed]
                ]
            ]);

            // Delete the channel after a short delay
            sleep(2);
            
            $response = $this->client->delete("https://discord.com/api/v10/channels/{$session->discord_channel_id}", [
                'headers' => [
                    'Authorization' => 'Bot ' . $this->botToken
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            Log::error('Failed to delete Discord channel', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'channel_id' => $session->discord_channel_id
            ]);
            return false;
        }
    }

    /**
     * Get messages from a Discord channel to check for new agent replies
     */
    public function getChannelMessages(string $channelId, ?string $after = null): array
    {
        if (!$this->botToken) {
            return [];
        }

        try {
            $url = "https://discord.com/api/v10/channels/{$channelId}/messages?limit=50";
            if ($after) {
                $url .= "&after={$after}";
            }

            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bot ' . $this->botToken
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody(), true);
            }

            return [];
        } catch (RequestException $e) {
            Log::error('Failed to get Discord channel messages', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId
            ]);
            return [];
        }
    }

    /**
     * Send a message to Discord channel using bot token
     */
    public function sendBotMessage(string $channelId, string $message): bool
    {
        if (!$this->botToken) {
            Log::error('Discord bot token not configured');
            return false;
        }

        try {
            $response = $this->client->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                'headers' => [
                    'Authorization' => 'Bot ' . $this->botToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'content' => $message
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            Log::error('Failed to send Discord bot message', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId
            ]);
            return false;
        }
    }

    /**
     * Truncate message to fit Discord embed limits
     */
    private function truncateMessage(string $message, int $limit = 1024): string
    {
        if (strlen($message) <= $limit) {
            return $message;
        }

        return substr($message, 0, $limit - 3) . '...';
    }

    /**
     * Format duration between two timestamps
     */
    private function formatDuration($start, $end): string
    {
        $diff = $start->diffInMinutes($end);
        
        if ($diff < 60) {
            return "{$diff} minutes";
        }

        $hours = floor($diff / 60);
        $minutes = $diff % 60;

        return "{$hours}h {$minutes}m";
    }
}
