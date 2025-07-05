<?php

namespace ADReece\LaracordLiveChat\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ADReece\LaracordLiveChat\LaracordLiveChatServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaracordLiveChatServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set test configuration
        config()->set('laracord-live-chat.discord.webhook_url', 'https://discord.com/api/webhooks/test');
        config()->set('laracord-live-chat.discord.bot_token', 'test_bot_token');
        config()->set('laracord-live-chat.discord.guild_id', '123456789');
        config()->set('laracord-live-chat.discord.category_id', '987654321');

        config()->set('laracord-live-chat.scheduler.discord_monitoring.enabled', true);
        config()->set('laracord-live-chat.scheduler.discord_monitoring.frequency', 'everyMinute');
        config()->set('laracord-live-chat.scheduler.cleanup.enabled', true);
        config()->set('laracord-live-chat.scheduler.cleanup.time', '02:00');
    }
}
