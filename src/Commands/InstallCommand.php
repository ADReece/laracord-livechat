<?php

namespace ADReece\LaracordLiveChat\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'laracord:install'; // Fixed: changed from 'laracord-chat:install'
    protected $description = 'Install Laracord Live Chat package';

    public function handle()
    {
        $this->info('Installing Laracord Live Chat...');

        // Publish config
        $this->call('vendor:publish', [
            '--provider' => 'ADReece\\LaracordLiveChat\\LaracordLiveChatServiceProvider',
            '--tag' => 'config'
        ]);

        // Publish views
        $this->call('vendor:publish', [
            '--provider' => 'ADReece\\LaracordLiveChat\\LaracordLiveChatServiceProvider',
            '--tag' => 'views'
        ]);

        // Run migrations
        $this->call('migrate');

        $this->info('✅ Laracord Live Chat installed successfully!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Configure your Discord bot token in .env: DISCORD_BOT_TOKEN=your_bot_token');
        $this->line('2. Configure your Discord guild ID in .env: DISCORD_GUILD_ID=your_guild_id');
        $this->line('3. Configure Pusher credentials for real-time messaging');
        $this->line('4. Include the chat widget in your views: @include(\'laracord-live-chat::include\')');
        $this->line('5. Set up Discord slash commands using: php artisan laracord-chat:setup-discord');
        $this->line('');
        $this->info('📅 Scheduler Setup (Important!):');
        $this->line('The package uses Laravel\'s built-in scheduler for Discord monitoring.');
        $this->line('Make sure you have this cron entry on your server:');
        $this->comment('* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1');
        $this->line('');
        $this->line('Check scheduler status anytime with: php artisan laracord-chat:schedule-status');
    }
}
