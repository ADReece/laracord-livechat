<?php

namespace ADReece\LaracordLiveChat\Tests\Unit;

use ADReece\LaracordLiveChat\LaracordLiveChatServiceProvider;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;

class LaracordLiveChatServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_services()
    {
        $this->assertTrue($this->app->bound(\ADReece\LaracordLiveChat\Services\DiscordService::class));
        $this->assertTrue($this->app->bound(\ADReece\LaracordLiveChat\Services\ChatService::class));
        $this->assertTrue($this->app->bound(\ADReece\LaracordLiveChat\Services\DiscordMessageMonitor::class));
    }

    /** @test */
    public function it_publishes_config()
    {
        $this->artisan('vendor:publish', [
            '--provider' => LaracordLiveChatServiceProvider::class,
            '--tag' => 'laracord-config',
        ])->assertExitCode(0);

        $this->assertFileExists(config_path('laracord-live-chat.php'));
    }

    /** @test */
    public function it_publishes_views()
    {
        $this->artisan('vendor:publish', [
            '--provider' => LaracordLiveChatServiceProvider::class,
            '--tag' => 'laracord-views',
        ])->assertExitCode(0);

        $this->assertFileExists(resource_path('views/vendor/laracord-live-chat/widget.blade.php'));
        $this->assertFileExists(resource_path('views/vendor/laracord-live-chat/include.blade.php'));
    }

    /** @test */
    public function it_loads_migrations()
    {
        $this->assertTrue(\Schema::hasTable('chat_sessions'));
        $this->assertTrue(\Schema::hasTable('chat_messages'));
    }

    /** @test */
    public function it_registers_routes()
    {
        $routes = Route::getRoutes();
        
        $routeNames = [];
        foreach ($routes as $route) {
            if ($route->getName()) {
                $routeNames[] = $route->getName();
            }
        }

        $this->assertContains('laracord.chat.start', $routeNames);
        $this->assertContains('laracord.chat.send', $routeNames);
        $this->assertContains('laracord.chat.close', $routeNames);
        $this->assertContains('laracord.discord.webhook', $routeNames);
    }

    /** @test */
    public function it_registers_commands()
    {
        $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')->all();
        
        $commandNames = array_keys($commands);

        $this->assertContains('laracord:install', $commandNames);
        $this->assertContains('laracord:discord-bot', $commandNames);
        $this->assertContains('laracord:monitor-discord', $commandNames);
        $this->assertContains('laracord:schedule-status', $commandNames);
    }

    /** @test */
    public function it_schedules_jobs_when_enabled()
    {
        config([
            'laracord-live-chat.monitoring.enabled' => true,
            'laracord-live-chat.cleanup.enabled' => true,
        ]);

        $schedule = $this->app->make(Schedule::class);
        $events = $schedule->events();

        $jobCommands = collect($events)->map(function ($event) {
            return $event->command ?? null;
        })->filter()->toArray();

        // Check that our jobs are scheduled
        $this->assertTrue(
            collect($jobCommands)->contains(function ($command) {
                return str_contains($command, 'MonitorDiscordMessages');
            })
        );

        $this->assertTrue(
            collect($jobCommands)->contains(function ($command) {
                return str_contains($command, 'CleanupChatSessions');
            })
        );
    }

    /** @test */
    public function it_does_not_schedule_jobs_when_disabled()
    {
        config([
            'laracord-live-chat.monitoring.enabled' => false,
            'laracord-live-chat.cleanup.enabled' => false,
        ]);

        // Re-register the service provider to apply the disabled config
        $provider = new LaracordLiveChatServiceProvider($this->app);
        $provider->boot();

        $schedule = $this->app->make(Schedule::class);
        $events = $schedule->events();

        $jobCommands = collect($events)->map(function ($event) {
            return $event->command ?? null;
        })->filter()->toArray();

        // Should not contain our jobs when disabled
        $this->assertFalse(
            collect($jobCommands)->contains(function ($command) {
                return str_contains($command, 'MonitorDiscordMessages');
            })
        );
    }

    /** @test */
    public function it_loads_views()
    {
        $viewPath = __DIR__ . '/../../resources/views';
        
        $this->assertTrue(view()->exists('laracord-live-chat::widget'));
        $this->assertTrue(view()->exists('laracord-live-chat::include'));
    }

    /** @test */
    public function it_registers_event_listeners()
    {
        $listeners = $this->app->make('events')->getListeners();
        
        // Check that broadcasting is set up for our events
        $this->assertArrayHasKey('ADReece\LaracordLiveChat\Events\MessageSent', $listeners);
        $this->assertArrayHasKey('ADReece\LaracordLiveChat\Events\SessionStarted', $listeners);
        $this->assertArrayHasKey('ADReece\LaracordLiveChat\Events\SessionClosed', $listeners);
    }

    /** @test */
    public function it_merges_config_correctly()
    {
        $config = config('laracord-live-chat');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('discord', $config);
        $this->assertArrayHasKey('pusher', $config);
        $this->assertArrayHasKey('monitoring', $config);
        $this->assertArrayHasKey('cleanup', $config);
    }

    /** @test */
    public function it_sets_default_configuration_values()
    {
        $config = config('laracord-live-chat');
        
        $this->assertEquals(60, $config['session_timeout']); // minutes
        $this->assertEquals('everyMinute', $config['monitoring']['frequency']);
        $this->assertEquals('02:00', $config['cleanup']['time']);
        $this->assertTrue($config['monitoring']['enabled']);
        $this->assertTrue($config['cleanup']['enabled']);
    }

    /** @test */
    public function it_handles_environment_configuration()
    {
        // Set some environment variables
        config([
            'laracord-live-chat.discord.bot_token' => 'test_token',
            'laracord-live-chat.discord.guild_id' => 'test_guild',
        ]);

        $this->assertEquals('test_token', config('laracord-live-chat.discord.bot_token'));
        $this->assertEquals('test_guild', config('laracord-live-chat.discord.guild_id'));
    }
}
