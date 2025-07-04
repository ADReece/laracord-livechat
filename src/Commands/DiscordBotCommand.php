<?php

namespace ADReece\LaracordLiveChat\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class DiscordBotCommand extends Command
{
    protected $signature = 'laracord-chat:setup-discord';
    protected $description = 'Set up Discord slash commands for the chat bot';

    public function handle()
    {
        $botToken = config('laracord-live-chat.discord.bot_token');
        $guildId = config('laracord-live-chat.discord.guild_id');

        if (!$botToken) {
            $this->error('Discord bot token not configured. Please set DISCORD_BOT_TOKEN in your .env file.');
            return 1;
        }

        if (!$guildId) {
            $this->error('Discord guild ID not configured. Please set DISCORD_GUILD_ID in your .env file.');
            return 1;
        }

        $this->info('Setting up Discord slash commands...');

        $client = new Client();
        $applicationId = $this->getApplicationId($client, $botToken);

        if (!$applicationId) {
            $this->error('Failed to get Discord application ID.');
            return 1;
        }

        $commands = [
            [
                'name' => 'sessions',
                'description' => 'List all active chat sessions'
            ],
            [
                'name' => 'close',
                'description' => 'Close a chat session',
                'options' => [
                    [
                        'type' => 3, // STRING
                        'name' => 'session_id',
                        'description' => 'The chat session ID to close',
                        'required' => true
                    ]
                ]
            ]
        ];

        try {
            $url = "https://discord.com/api/v10/applications/{$applicationId}/guilds/{$guildId}/commands";
            
            foreach ($commands as $command) {
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => 'Bot ' . $botToken,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $command
                ]);

                if ($response->getStatusCode() === 201) {
                    $this->info("✅ Created command: /{$command['name']}");
                } else {
                    $this->error("❌ Failed to create command: /{$command['name']}");
                }
            }

            $this->info('');
            $this->info('Discord slash commands setup complete!');
            $this->info('');
            $this->info('Available commands in your Discord server:');
            $this->line('• /sessions - List active chat sessions');
            $this->line('• /close <session_id> - Close a chat session');
            $this->info('');
            $this->info('Chat Management:');
            $this->line('• Each new chat creates a dedicated Discord channel');
            $this->line('• Simply type messages in the channel to reply to customers');
            $this->line('• Channels are automatically deleted when chats are closed');
            $this->info('');
            $this->info('📅 Discord Monitoring:');
            $this->line('• The package automatically monitors Discord channels via Laravel scheduler');
            $this->line('• Make sure your cron is set up: php artisan schedule:run');
            $this->line('• Check status with: php artisan laracord-chat:schedule-status');

        } catch (\Exception $e) {
            $this->error('Failed to set up Discord commands: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getApplicationId(Client $client, string $botToken): ?string
    {
        try {
            $response = $client->get('https://discord.com/api/v10/applications/@me', [
                'headers' => [
                    'Authorization' => 'Bot ' . $botToken
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['id'] ?? null;
        } catch (\Exception $e) {
            $this->error('Failed to get application ID: ' . $e->getMessage());
            return null;
        }
    }
}
