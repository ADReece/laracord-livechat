<?php

namespace ADReece\LaracordLiveChat\Commands;

use Illuminate\Console\Command;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;
use ADReece\LaracordLiveChat\Services\ChatService;

class ScheduleStatusCommand extends Command
{
    protected $signature = 'laracord-chat:schedule-status';
    protected $description = 'Show the status of Laracord Live Chat scheduled tasks';

    public function handle()
    {
        $this->info('Laracord Live Chat - Scheduled Tasks Status');
        $this->line('');

        // Check Discord monitoring schedule
        $discordEnabled = config('laracord-live-chat.scheduler.discord_monitoring.enabled', true);
        $discordFrequency = config('laracord-live-chat.scheduler.discord_monitoring.frequency', 'everyMinute');
        
        $this->line('📡 Discord Message Monitoring:');
        $this->line('   Status: ' . ($discordEnabled ? '🟢 Enabled' : '🔴 Disabled'));
        $this->line('   Frequency: ' . $discordFrequency);
        $this->line('');

        // Check cleanup schedule
        $cleanupEnabled = config('laracord-live-chat.scheduler.cleanup.enabled', true);
        $cleanupTime = config('laracord-live-chat.scheduler.cleanup.time', '02:00');
        
        $this->line('🧹 Session Cleanup:');
        $this->line('   Status: ' . ($cleanupEnabled ? '🟢 Enabled' : '🔴 Disabled'));
        $this->line('   Time: Daily at ' . $cleanupTime);
        $this->line('');

        // Show active sessions
        $chatService = app(ChatService::class);
        $activeSessions = $chatService->getActiveSessions();
        
        $this->line('💬 Current Active Sessions: ' . $activeSessions->count());
        $this->line('');

        // Instructions
        $this->info('To ensure scheduled tasks run, make sure you have added this to your crontab:');
        $this->line('* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1');
        $this->line('');
        
        $this->info('Configuration can be adjusted in your .env file:');
        $this->line('LARACORD_DISCORD_MONITORING_ENABLED=true');
        $this->line('LARACORD_DISCORD_MONITORING_FREQUENCY=everyMinute');
        $this->line('LARACORD_CLEANUP_ENABLED=true');
        $this->line('LARACORD_CLEANUP_TIME=02:00');
    }
}
