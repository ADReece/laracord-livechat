<?php

namespace ADReece\LaracordLiveChat;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Services\ChatService;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;
use ADReece\LaracordLiveChat\Jobs\MonitorDiscordMessages;
use ADReece\LaracordLiveChat\Jobs\CleanupChatSessions;

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

        // Publish configuration files
        $this->publishes([
            __DIR__ . '/../config/laracord-live-chat.php' => config_path('laracord-live-chat.php'),
        ], 'config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laracord-live-chat'),
        ], 'views');

        // Register commands only if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
                Commands\DiscordBotCommand::class,
                Commands\MonitorDiscordChannelsCommand::class,
                Commands\ScheduleStatusCommand::class,
            ]);
        }

        // Register scheduled tasks automatically if scheduler is available
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $this->registerScheduledTasks($schedule);
        });
    }

    /**
     * Register the package's scheduled tasks with Laravel's scheduler.
     */
    public function registerScheduledTasks(Schedule $schedule)
    {
        // Discord monitoring schedule
        if (config('laracord-live-chat.scheduler.discord_monitoring.enabled', true)) {
            $frequency = config('laracord-live-chat.scheduler.discord_monitoring.frequency', 'everyMinute');

            $event = $schedule->job(MonitorDiscordMessages::class);

            // Apply the configured frequency
            switch ($frequency) {
                case 'everyTwoMinutes':
                    $event->everyTwoMinutes();
                    break;
                case 'everyFiveMinutes':
                    $event->everyFiveMinutes();
                    break;
                case 'everyMinute':
                default:
                    $event->everyMinute();
                    break;
            }

            $event->withoutOverlapping();
        }

        // Session cleanup schedule
        if (config('laracord-live-chat.scheduler.cleanup.enabled', true)) {
            $cleanupTime = config('laracord-live-chat.scheduler.cleanup.time', '02:00');

            $schedule->job(CleanupChatSessions::class)
                ->dailyAt($cleanupTime)
                ->withoutOverlapping();
        }
    }

    /**
     * Static method to register scheduled tasks (for manual registration if needed).
     */
    public static function scheduleWith(Schedule $schedule)
    {
        $provider = new static(app());
        $provider->registerScheduledTasks($schedule);
    }
}
