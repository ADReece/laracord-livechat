<?php

namespace ADReece\LaracordLiveChat;

use Illuminate\Support\ServiceProvider;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Services\ChatService;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;

class LaracordLiveChatServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laracord-live-chat.php',
            'laracord-live-chat'
        );

        $this->app->singleton(DiscordService::class, function ($app) {
            return new DiscordService(
                config('laracord-live-chat.discord.webhook_url'),
                config('laracord-live-chat.discord.bot_token')
            );
        });

        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService(
                $app->make(DiscordService::class)
            );
        });

        $this->app->singleton(DiscordMessageMonitor::class, function ($app) {
            return new DiscordMessageMonitor(
                $app->make(DiscordService::class),
                $app->make(ChatService::class)
            );
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laracord-live-chat');

        // Schedule Discord message monitoring (skip during testing)
        if (!$this->app->environment('testing')) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                
                // Monitor Discord channels based on configuration
                if (config('laracord-live-chat.scheduler.discord_monitoring.enabled', true)) {
                    $frequency = config('laracord-live-chat.scheduler.discord_monitoring.frequency', 'everyMinute');
                    
                    $job = $schedule->job(\ADReece\LaracordLiveChat\Jobs\MonitorDiscordMessages::class)
                        ->withoutOverlapping();
                    
                    // Don't run in background during tests
                    if (!app()->environment('testing')) {
                        $job->runInBackground();
                    }
                    
                    // Apply frequency based on config
                    switch ($frequency) {
                        case 'everyTwoMinutes':
                            $job->everyTwoMinutes();
                            break;
                        case 'everyFiveMinutes':
                            $job->everyFiveMinutes();
                            break;
                        default:
                            $job->everyMinute();
                            break;
                    }
                }
                
                // Clean up old sessions daily
                if (config('laracord-live-chat.scheduler.cleanup.enabled', true)) {
                    $schedule->job(\ADReece\LaracordLiveChat\Jobs\CleanupChatSessions::class)
                        ->daily()
                        ->at(config('laracord-live-chat.scheduler.cleanup.time', '02:00'));
                }
            });
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laracord-live-chat.php' => config_path('laracord-live-chat.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/laracord-live-chat'),
            ], 'views');

            $this->commands([
                Commands\InstallCommand::class,
                Commands\DiscordBotCommand::class,
                Commands\MonitorDiscordChannelsCommand::class,
                Commands\ScheduleStatusCommand::class,
            ]);
        }
    }
}
