<?php

namespace Swoopy\LaracordLiveChat;

use Illuminate\Support\ServiceProvider;
use Swoopy\LaracordLiveChat\Services\DiscordService;
use Swoopy\LaracordLiveChat\Services\ChatService;
use Swoopy\LaracordLiveChat\Services\DiscordMessageMonitor;

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
            ]);
        }
    }
}
