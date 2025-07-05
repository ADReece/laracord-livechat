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

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set up test configuration
        config()->set('laracord-live-chat.discord.bot_token', 'test_bot_token');
        config()->set('laracord-live-chat.discord.guild_id', 'test_guild_id');
        config()->set('laracord-live-chat.discord.webhook_url', 'https://discord.com/api/webhooks/test');
        config()->set('laracord-live-chat.pusher.key', 'test_pusher_key');
        config()->set('laracord-live-chat.pusher.secret', 'test_pusher_secret');
        config()->set('laracord-live-chat.pusher.app_id', 'test_pusher_app_id');
        config()->set('laracord-live-chat.pusher.cluster', 'mt1');
    }
}
