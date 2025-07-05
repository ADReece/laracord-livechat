<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Jobs;

use ADReece\LaracordLiveChat\Jobs\MonitorDiscordMessages;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Mockery;

class MonitorDiscordMessagesTest extends TestCase
{
    private $monitor;
    private $job;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitor = Mockery::mock(DiscordMessageMonitor::class);
        $this->app->instance(DiscordMessageMonitor::class, $this->monitor);
        
        $this->job = new MonitorDiscordMessages();
    }

    /** @test */
    public function it_monitors_discord_messages_successfully()
    {
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andReturn(true);

        $result = $this->job->handle($this->monitor);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_monitoring_failure()
    {
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andReturn(false);

        $result = $this->job->handle($this->monitor);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_exceptions_during_monitoring()
    {
        $exception = new \Exception('Discord API error');
        
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Discord message monitoring failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

        $result = $this->job->handle($this->monitor);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_has_correct_job_properties()
    {
        $this->assertEquals(3, $this->job->tries);
        $this->assertEquals(60, $this->job->timeout);
        $this->assertFalse($this->job->failOnTimeout);
    }

    /** @test */
    public function it_can_be_retried_on_failure()
    {
        // Simulate first failure
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andThrow(new \Exception('Temporary failure'));

        Log::shouldReceive('error')->once();

        $result = $this->job->handle($this->monitor);

        $this->assertFalse($result);
        
        // Job should be retryable since tries = 3
        $this->assertTrue($this->job->tries > 1);
    }

    /** @test */
    public function it_handles_network_timeouts_gracefully()
    {
        $timeoutException = new \GuzzleHttp\Exception\ConnectException(
            'Connection timeout',
            new \GuzzleHttp\Psr7\Request('GET', 'test')
        );
        
        $this->monitor
            ->shouldReceive('monitorMessages')
            ->once()
            ->andThrow($timeoutException);

        Log::shouldReceive('error')
            ->once()
            ->with('Discord message monitoring failed', Mockery::type('array'));

        $result = $this->job->handle($this->monitor);

        $this->assertFalse($result);
        $this->assertFalse($this->job->failOnTimeout);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
