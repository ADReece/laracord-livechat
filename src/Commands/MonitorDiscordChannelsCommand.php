<?php

namespace Swoopy\LaracordLiveChat\Commands;

use Illuminate\Console\Command;
use Swoopy\LaracordLiveChat\Services\DiscordMessageMonitor;
use Illuminate\Support\Facades\Log;

class MonitorDiscordChannelsCommand extends Command
{
    protected $signature = 'laracord-chat:monitor-discord {--once : Run once instead of continuously}';
    protected $description = 'Monitor Discord channels for new agent messages';

    private DiscordMessageMonitor $monitor;

    public function __construct(DiscordMessageMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function handle()
    {
        if (!config('laracord-live-chat.discord.bot_token')) {
            $this->error('Discord bot token not configured. Please set DISCORD_BOT_TOKEN in your .env file.');
            return 1;
        }

        $this->info('Starting Discord channel monitoring...');

        if ($this->option('once')) {
            $this->runOnce();
        } else {
            $this->runContinuously();
        }

        return 0;
    }

    private function runOnce(): void
    {
        $this->info('Checking Discord channels for new messages...');
        
        try {
            $this->monitor->monitorActiveChannels();
            $this->info('✅ Discord channel check completed');
        } catch (\Exception $e) {
            $this->error('❌ Error monitoring Discord channels: ' . $e->getMessage());
            Log::error('Discord monitoring error', ['error' => $e->getMessage()]);
        }
    }

    private function runContinuously(): void
    {
        $this->info('Monitoring Discord channels continuously. Press Ctrl+C to stop.');
        
        while (true) {
            try {
                $this->monitor->monitorActiveChannels();
                $this->line('✓ Checked at ' . now()->format('H:i:s'));
            } catch (\Exception $e) {
                $this->error('❌ Error: ' . $e->getMessage());
                Log::error('Discord monitoring error', ['error' => $e->getMessage()]);
            }

            // Wait 5 seconds before next check
            sleep(5);
        }
    }
}
