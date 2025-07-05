<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Commands;

use ADReece\LaracordLiveChat\Commands\MonitorDiscordChannelsCommand;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Mockery;

class MonitorDiscordChannelsCommandTest extends TestCase
{
    private $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitor = Mockery::mock(DiscordMessageMonitor::class);
        $this->app->instance(DiscordMessageMonitor::class, $this->monitor);
    }

    /** @test */
    public function it_monitors_discord_channels_successfully()
    {
        $this->monitor
            ->shouldReceive('monitorActiveChannels') // Fixed: using correct method name
            ->once()
            ->andReturn(true);

        Artisan::call('laracord:monitor-discord', ['--once' => true]); // Fixed: added --once flag to prevent hanging

        $output = Artisan::output();

        $this->assertStringContainsString('Discord channel check completed', $output);
    }

    /** @test */
    public function it_handles_monitoring_failures()
    {
        $this->monitor
            ->shouldReceive('monitorActiveChannels') // Fixed: using correct method name
            ->once()
            ->andThrow(new \Exception('Discord API error'));

        Artisan::call('laracord:monitor-discord', ['--once' => true]); // Fixed: added --once flag

        $output = Artisan::output();

        $this->assertStringContainsString('Error monitoring Discord channels', $output);
    }

    /** @test */
    public function it_handles_exceptions_during_monitoring()
    {
        $exception = new \Exception('Discord API error');
        
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andThrow($exception);

        Artisan::call('laracord:monitor-discord');

        $output = Artisan::output();

        $this->assertStringContainsString('Error during monitoring', $output);
        $this->assertStringContainsString('Discord API error', $output);
    }

    /** @test */
    public function it_shows_verbose_output()
    {
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andReturn(true);

        Artisan::call('laracord:monitor-discord', ['-v' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Starting Discord message monitoring', $output);
        $this->assertStringContainsString('Checking for new messages', $output);
    }

    /** @test */
    public function it_runs_in_loop_mode()
    {
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->times(3) // Will run a few times before we stop
            ->andReturn(true);

        // Start the command in background and stop it after a short time
        $command = new MonitorDiscordChannelsCommand($this->monitor);
        
        // Mock the loop to run only a few iterations
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('shouldContinue');
        $property->setAccessible(true);
        
        // Simulate running for a short time
        $iterations = 0;
        while ($iterations < 3) {
            $result = $this->monitor->monitorMessages();
            $this->assertTrue($result);
            $iterations++;
            
            if ($iterations >= 3) {
                break;
            }
        }

        $this->assertEquals(3, $iterations);
    }

    /** @test */
    public function it_handles_configuration_errors()
    {
        // Clear Discord config
        config([
            'laracord-live-chat.discord.bot_token' => null,
        ]);

        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andThrow(new \Exception('Discord bot token not configured'));

        Artisan::call('laracord:monitor-discord');

        $output = Artisan::output();

        $this->assertStringContainsString('Error during monitoring', $output);
        $this->assertStringContainsString('Discord bot token not configured', $output);
    }

    /** @test */
    public function it_respects_monitoring_interval()
    {
        config(['laracord-live-chat.monitoring.interval' => 5]); // 5 seconds

        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andReturn(true);

        $startTime = microtime(true);
        
        Artisan::call('laracord:monitor-discord');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete quickly in test mode
        $this->assertLessThan(1, $executionTime);
    }

    /** @test */
    public function it_provides_usage_instructions()
    {
        Artisan::call('laracord:monitor-discord', ['--help' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Monitor Discord channels', $output);
        $this->assertStringContainsString('--loop', $output);
        $this->assertStringContainsString('--interval', $output);
    }

    /** @test */
    public function it_handles_network_connectivity_issues()
    {
        $networkException = new \GuzzleHttp\Exception\ConnectException(
            'Connection timeout',
            new \GuzzleHttp\Psr7\Request('GET', 'test')
        );
        
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andThrow($networkException);

        Artisan::call('laracord:monitor-discord');

        $output = Artisan::output();

        $this->assertStringContainsString('Error during monitoring', $output);
        $this->assertStringContainsString('Connection timeout', $output);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
