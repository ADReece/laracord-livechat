<?php

namespace Swoopy\LaracordLiveChat\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'laracord-chat:install';
    protected $description = 'Install Laracord Live Chat package';

    public function handle()
    {
        $this->info('Installing Laracord Live Chat...');

        // Publish config
        $this->call('vendor:publish', [
            '--provider' => 'Swoopy\\LaracordLiveChat\\LaracordLiveChatServiceProvider',
            '--tag' => 'config'
        ]);

        // Publish views
        $this->call('vendor:publish', [
            '--provider' => 'Swoopy\\LaracordLiveChat\\LaracordLiveChatServiceProvider',
            '--tag' => 'views'
        ]);

        // Run migrations
        $this->call('migrate');

        $this->info('✅ Laracord Live Chat installed successfully!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Configure your Discord webhook URL in .env: DISCORD_WEBHOOK_URL=your_webhook_url');
        $this->line('2. Configure your Discord bot token in .env: DISCORD_BOT_TOKEN=your_bot_token');
        $this->line('3. Configure Pusher credentials for real-time messaging');
        $this->line('4. Include the chat widget in your views: @include(\'laracord-live-chat::widget\')');
        $this->line('5. Set up Discord slash commands using: php artisan laracord-chat:setup-discord');
    }
}
